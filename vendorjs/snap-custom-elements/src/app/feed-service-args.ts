import {MoodleResArgs} from "./moodle-res-args";

export class FeedServiceArgs extends MoodleResArgs {
  feedid: string;
  page: number;
  pagesize: number;

  getHash(): string | number {
    return this.stringToHash(`${this.feedid}${this.page}${this.pagesize}`);
  }
}
