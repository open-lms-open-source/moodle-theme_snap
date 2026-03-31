import {Injectable} from '@angular/core';

import {Observable, of} from 'rxjs';

import {MoodleService} from "./moodle.service";
import {map, tap} from "rxjs/operators";

@Injectable({
  providedIn: 'root'
})

export class StringService {

  constructor(
    private moodleService: MoodleService
  ) {
  }

  getStrings(stringIds: string[]): Observable<string[]> {
    const methodName = 'core_get_strings';

    let strArgs = [], cachedIds: string[] = [];
    for (let i = 0; i < stringIds.length; i++) {
      let stringId = stringIds[i];
      let component = 'theme_snap';
      if (stringId.indexOf(':') != -1) {
        const splitted = stringIds[i].split(':');
        stringId = splitted[0];
        component = splitted[1];
      }
      if (this.getCachedString(stringId)) {
        cachedIds.push(stringId);
        continue;
      }
      strArgs.push({
        stringid: stringId,
        component: component
      });
    }

    if (strArgs.length === 0) {
      return of(this.processStrings(cachedIds, []));
    }

    const requestBody = {
      strings: strArgs
    };

    return this.moodleService.service(methodName, requestBody).pipe(
      map((x) => this.moodleService.extractData(x)),
      map(stringData => {
        return this.processStrings(cachedIds, stringData);
      })
    );
  }

  processStrings(cachedIds: string[], stringData: any[]) : string[] {
    let res: string[] = [];

    // Look for cached strings.
    for (let i = 0; i < cachedIds.length; i++) {
      res[cachedIds[i]] = this.getCachedString(cachedIds[i]);
    }

    // Get strings from request and cache them too.
    for(let i = 0; i < stringData.length; i++) {
      res[stringData[i].stringid] = stringData[i].string;
      this.setCachedString(stringData[i].stringid, stringData[i].string);
    }

    return res;
  }

  getCachedString(stringId: string): string {
    const cacheRaw = sessionStorage.getItem('snapStringCache');
    if (!cacheRaw) return undefined;
    try {
      const cache = JSON.parse(cacheRaw);
      return cache[stringId];
    } catch (e) {
      return undefined;
    }
  }

  setCachedString(stringId: string, value: string): void {
    let cache: {[key: string]: string} = {};
    const cacheRaw = sessionStorage.getItem('snapStringCache');
    if (cacheRaw) {
      try {
        cache = JSON.parse(cacheRaw);
      } catch (e) {
        cache = {};
      }
    }
    cache[stringId] = value;
    sessionStorage.setItem('snapStringCache', JSON.stringify(cache));
  }
}
