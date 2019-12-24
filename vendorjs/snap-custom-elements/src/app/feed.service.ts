import {Injectable} from '@angular/core';

import {Observable, of} from 'rxjs';

import {HttpClient, HttpHeaders} from '@angular/common/http';

import {catchError, map, tap} from 'rxjs/operators';
import {MoodleRes} from "./moodle.res";

@Injectable({
  providedIn: 'root'
})
export class FeedService {

  private moodleAjaxUrl = '/lib/ajax/service.php';  // URL to Moodle ajax api.

  httpOptions = {
    headers: new HttpHeaders({ 'Content-Type': 'application/json' })
  };

  constructor(
    private http: HttpClient) {
  }

  getFeed(wwwRoot: string, sessKey: string|undefined, feedId: string, page: number, pageSize: number, maxId: number): Observable<MoodleRes[]> {
    const errorRes : MoodleRes[] = [{
      error: "No session key present",
      data: undefined
    }];
    if (!sessKey) {
      return of(errorRes);
    }

    let body = [{
      index: 0,
      methodname: 'theme_snap_feed',
      args: {
        feedid: feedId,
        page: page,
        pagesize: pageSize,
        maxid: maxId
      }
    }];

    return this.http.post<MoodleRes[]>(`${wwwRoot}${this.moodleAjaxUrl}?sesskey=${sessKey}`, body, this.httpOptions)
      .pipe(
        tap(_ => this.log('fetched feed')),
        catchError(this.handleError<MoodleRes[]>('getFeed', errorRes))
      );
  }

  private log(message: string) {}

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

      // TODO: better job of transforming error for user consumption
      this.log(`${operation} failed: ${error.message}`);

      // Let the app keep running by returning an empty result.
      return of(result as T);
    };
  }
}
