import { Component, OnInit } from '@angular/core';
import {StringService} from "../string.service";
import {ErrorReporterService} from "../error-reporter.service";

@Component({
  selector: 'feed-error-modal',
  template: `
    <button id="openErrorModalButton" [hidden]="true" data-toggle="modal" data-target="#errorModal"></button>
    <!-- Modal -->
    <div class="modal fade" id="errorModal" tabindex="-1" role="dialog" aria-labelledby="errorModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="errorModallLabel">{{strings['error']}}</h5>
            <button type="button" class="close" data-dismiss="modal" [attr.aria-label]="strings['close']">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="feed-dialog-exception modal-body">
            <div class="feed-exception-message" [innerHTML]="error.message"></div>
            <div class="feed-exception-param param-stacktrace" *ngIf="error.backtrace">
              <label>Stack trace:</label>
              <pre>{{error.backtrace}}
              </pre>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-primary" data-dismiss="modal">{{strings['close']}}</button>
          </div>
        </div>
      </div>
    </div>
`,
  styles: [
    '.feed-dialog-exception .def-report-exception-param {margin-bottom: .5em;}',
    '.feed-dialog-exception .param-stacktrace label {display: block;margin: 0;padding: 3px 1em; width: 150px;}',
    '.feed-dialog-exception .param-stacktrace pre {display: block;height: 200px;}',
    '.feed-dialog-exception .param-stacktrace label {background-color: #eee;border: 1px solid #ccc;border-bottom-width: 0;}',
    '.feed-dialog-exception .param-stacktrace pre {border: 1px solid #ccc;background-color: #fff;padding: .5rem;}',
    '.feed-dialog-exception {padding: 1.5rem;padding-top: 0;}',
    '.feed-dialog-exception .def-report-exception-message {margin: 1em;}'
  ]
})
export class FeedErrorModalComponent implements OnInit {

  strings: string[];
  error: any;

  constructor(
    private errorReporterService: ErrorReporterService,
    private stringService: StringService
  ) {
    this.error = {message: null, backtrace: null};
  }

  ngOnInit() {
    this.errorReporterService.registerModal(this);
    this.getStrings();
  }

  getStrings() {
    this.strings = [];
    this.stringService.getStrings([
      'error',
      'close',
    ]).subscribe(strings => {
      this.strings = strings;
    });
  }

  displayError(error: any) {
    let data = {message: null, backtrace: null};
    if (typeof error == 'object') {
      if (undefined !== error.exception.debuginfo) {
        data.backtrace = error.exception.debuginfo.trim() + " \n";
      }

      if (undefined !== error.exception.backtrace) {
        data.backtrace += error.exception.backtrace + " \n";
      }

      data.message = error.exception.message;
      this.error = data;
    } else {
      data.message = error;
      this.error = data;
    }
    document.getElementById("openErrorModalButton").click();
  }
}
