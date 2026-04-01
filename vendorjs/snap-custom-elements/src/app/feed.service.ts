import {Injectable} from '@angular/core';

import {finalize, Observable, of, shareReplay} from 'rxjs';

import {HttpClient, HttpHeaders} from '@angular/common/http';

import {catchError, map} from 'rxjs/operators';
import {MoodleRes} from "./moodle.res";
import {CachedMoodleRes} from "./cached-moodle-res";
import {MoodleResKey} from "./moodle-res-key";
import {FeedServiceArgs} from "./feed-service-args";

@Injectable({
  providedIn: 'root'
})
export class FeedService {

  private moodleAjaxUrl = '/lib/ajax/service.php';  // URL to Moodle ajax api.

  private maxLifeTime = 1800; // Default of 30 mins (1800 secs).

  private static readonly FEED_CACHE_KEY = 'themeSnapCachedPMFeedResults';

  private feedRequestMap = new Map<string, Observable<MoodleRes[]>>();

  httpOptions = {
    headers: new HttpHeaders({ 'Content-Type': 'application/json' })
  };

  constructor(
    private http: HttpClient) {
  }

  /**
   * Sets the max life time.
   * @param maxLifeTime
   */
  setMaxLifeTime(maxLifeTime: number) {
    this.maxLifeTime = maxLifeTime;
  }

  getFeed(wwwRoot: string, sessKey: string|undefined, feedId: string, page: number, pageSize: number, maxId: number, courseId: number): Observable<MoodleRes[]> {
    const errorRes : MoodleRes[] = [{
      error: "No session key present",
      data: undefined
    }];
    if (!sessKey) {
      return of(errorRes);
    }
    if (this.feedRequestMap.has(feedId)) {
      return this.feedRequestMap.get(feedId);
    }
    const moodleResKey: MoodleResKey = new MoodleResKey();
    moodleResKey.sessKey = sessKey;

    const feedServiceArgs: FeedServiceArgs = new FeedServiceArgs();
    feedServiceArgs.feedid = feedId;
    feedServiceArgs.page = page;
    feedServiceArgs.pagesize = pageSize;
    feedServiceArgs.courseId = courseId;

    moodleResKey.args = feedServiceArgs;
    const cachedRes = this.findDataInLocalCache(moodleResKey);

    let body = [{
      index: 0,
      methodname: 'theme_snap_feed',
      args: {
        feedid: feedId,
        page: page,
        pagesize: pageSize,
        maxid: maxId,
        courseid: courseId ?? undefined
      }
    }];

    // When a locally-cached result is available we still make a lightweight server request
    // for feeds that carry a cacheVersion token (e.g. deadlines).  If the token returned
    // by the server differs from the one stored in localStorage it means the backend MUC
    // cache was invalidated (e.g. a teacher edited activity dates) and we must serve the
    // fresh server response instead of the stale browser-cached one.
    if (cachedRes !== null) {
      const storedVersion = this.getStoredCacheVersion(moodleResKey);
      if (storedVersion === undefined) {
        // Feed does not carry versioning info — serve from localStorage as before.
        return of(cachedRes);
      }

      // Feed supports versioning: fetch from server and compare versions.
      const request = this.http.post<MoodleRes[]>(`${wwwRoot}${this.moodleAjaxUrl}?sesskey=${sessKey}`, body, this.httpOptions)
        .pipe(
          shareReplay(1),
          map(res => this.extractData(res)),
          map(res => {
            const freshVersion = this.extractCacheVersion(res);
            if (freshVersion !== undefined && freshVersion !== storedVersion) {
              // Server-side cache was updated — persist fresh data and return it.
              return this.storeDataInLocalCache(moodleResKey, res);
            }
            // Versions match: update the stored timestamp to extend the TTL, return cached.
            this.refreshCacheTimestamp(moodleResKey);
            return cachedRes;
          }),
          catchError(() => of(cachedRes)),
          finalize(() => {
            this.feedRequestMap.delete(feedId);
          })
        );
      this.feedRequestMap.set(feedId, request);
      return request;
    }

    const request = this.http.post<MoodleRes[]>(`${wwwRoot}${this.moodleAjaxUrl}?sesskey=${sessKey}`, body, this.httpOptions)
      .pipe(
        shareReplay(1),
        map(res => this.extractData(res)),
        map(res => this.storeDataInLocalCache(moodleResKey, res)),
        catchError(this.handleError<MoodleRes[]>('getFeed', errorRes)),
        finalize(() => {
          this.feedRequestMap.delete(feedId);
        })
      );
    this.feedRequestMap.set(feedId, request);
    return request;
  }

  public extractData(response: any) : MoodleRes[] {
    if (!response.length) {
      // Single response with error arrived.
      let singleMoodleRes: MoodleRes = response;
      return [singleMoodleRes];
    }

    let multiMoodleRes: MoodleRes[] = response;
    return multiMoodleRes;
  }

  /**
   * Handle Http operation that failed.
   * Let the app continue.
   * @param operation - name of the operation that failed
   * @param result - optional value to return as the observable result
   */
  private handleError<T>(operation = 'operation', result?: T) {
    return (error: any): Observable<T> => {

      // TODO: send the error to remote logging infrastructure
      console.error(error); // log to console instead

      // Let the app keep running by returning an empty result.
      return of(result as T);
    };
  }

  createLocalCacheKey(moodleResKey: MoodleResKey) : string {
    const contentHash = moodleResKey.args.getHash();
    return `${FeedService.FEED_CACHE_KEY}/${moodleResKey.sessKey}/${contentHash}`;
  }

  private findDataInLocalCache(moodleResKey: MoodleResKey) : null | MoodleRes[] {
    if (localStorage === undefined || this.maxLifeTime === 0) {
      return null;
    }

    // Attempt to get local stored item.
    const itemKey = this.createLocalCacheKey(moodleResKey);
    const serializedItem = localStorage.getItem(this.createLocalCacheKey(moodleResKey));
    if (serializedItem === null) {
      return null;
    }

    // Validate date.
    const cachedFeedRes: CachedMoodleRes = JSON.parse(serializedItem);
    const now: number = Date.now() / 1000; // JS way to get the current date timestamp in seconds.
    if (now > cachedFeedRes.timeCreated + this.maxLifeTime) {
      localStorage.removeItem(itemKey);
      return null;
    }

    return cachedFeedRes.result;
  }

  /**
   * Extract the cacheVersion token from the first item of a feed response, if present.
   * The server embeds this token (the MUC cache timestamp) in feed items that support
   * server-side cache versioning (e.g. deadlines).
   */
  private extractCacheVersion(res: MoodleRes[]): string | undefined {
    if (!res || !res[0] || !res[0].data || res[0].data.length === 0) {
      return undefined;
    }
    return res[0].data[0]?.cacheVersion;
  }

  /**
   * Return the cacheVersion stored in localStorage for this key, or undefined if the
   * entry does not exist or does not carry a version (feed does not support versioning).
   */
  private getStoredCacheVersion(moodleResKey: MoodleResKey): string | undefined {
    const serializedItem = localStorage.getItem(this.createLocalCacheKey(moodleResKey));
    if (serializedItem === null) {
      return undefined;
    }
    const cachedFeedRes: CachedMoodleRes = JSON.parse(serializedItem);
    return cachedFeedRes.cacheVersion;
  }

  /**
   * Reset the timeCreated timestamp of an existing localStorage entry so that the TTL
   * countdown restarts after a successful version-match check.
   */
  private refreshCacheTimestamp(moodleResKey: MoodleResKey): void {
    const itemKey = this.createLocalCacheKey(moodleResKey);
    const serializedItem = localStorage.getItem(itemKey);
    if (serializedItem === null) {
      return;
    }
    const cachedFeedRes: CachedMoodleRes = JSON.parse(serializedItem);
    cachedFeedRes.timeCreated = Date.now() / 1000;
    localStorage.setItem(itemKey, JSON.stringify(cachedFeedRes));
  }

  public storeDataInLocalCache(moodleResKey: MoodleResKey, res: MoodleRes[]) : MoodleRes[] {
    if (localStorage === undefined || this.maxLifeTime === 0 || res[0].error) {
      return res;
    }

    const itemKey = this.createLocalCacheKey(moodleResKey);
    let cachedFeedRes: CachedMoodleRes = {
      timeCreated : Date.now() / 1000, // JS way to get the current date timestamp in seconds.
      key : moodleResKey,
      result : res,
      cacheVersion: this.extractCacheVersion(res),
    };
    localStorage.setItem(itemKey, JSON.stringify(cachedFeedRes));

    return res;
  }

  public purgeDataInLocalCache(sessKey: string|undefined, feedId: string, page: number, pageSize: number, courseId: number) {
    if (localStorage === undefined || this.maxLifeTime === 0) {
      return;
    }

    const moodleResKey: MoodleResKey = new MoodleResKey();
    moodleResKey.sessKey = sessKey;

    const feedServiceArgs: FeedServiceArgs = new FeedServiceArgs();
    feedServiceArgs.feedid = feedId;
    feedServiceArgs.page = page;
    feedServiceArgs.pagesize = pageSize;
    feedServiceArgs.courseId = courseId;

    moodleResKey.args = feedServiceArgs;

    // Attempt to remove local stored item.
    const itemKey = this.createLocalCacheKey(moodleResKey);
    localStorage.removeItem(itemKey);
  }

  public purgeOtherDataInLocalCache(sessKey: string) {
    if (localStorage === undefined || this.maxLifeTime === 0) {
      return;
    }

    const cacheKey = `${FeedService.FEED_CACHE_KEY}/${sessKey}`;
    const cacheKeyLength = cacheKey.length;
    for (let i = localStorage.length - 1; i >= 0; i--) {
      const currentKey = localStorage.key(i);
      if (currentKey.indexOf(FeedService.FEED_CACHE_KEY) === 0
        && currentKey.substring(0, cacheKeyLength) !== cacheKey) {
        localStorage.removeItem(currentKey);
      }
    }
  }
}
