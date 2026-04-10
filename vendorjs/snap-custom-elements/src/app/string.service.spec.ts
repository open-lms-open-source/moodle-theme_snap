import {of} from "rxjs";

export class MockStringService {
  getString$(key: string, component?: string) {
    return of(key);
  }
}
