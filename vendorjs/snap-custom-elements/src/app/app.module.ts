import {BrowserModule} from '@angular/platform-browser';
import {CUSTOM_ELEMENTS_SCHEMA, DoBootstrap, Injector, NgModule, provideZoneChangeDetection} from '@angular/core';
import {provideAnimations} from '@angular/platform-browser/animations';

import {AppComponent} from './app.component';
import {FeedComponent} from './feed/feed.component';
import {createCustomElement} from "@angular/elements";
import {provideHttpClient} from "@angular/common/http";
import {FeedErrorModalComponent} from "./feed-error-modal/feed-error-modal.component";
import {MoodleStringPipe} from "openlms-angular-lib";

@NgModule({
  declarations: [
    AppComponent,
    FeedComponent,
    FeedErrorModalComponent,
  ],
  imports: [
    BrowserModule,
    MoodleStringPipe,
  ],
  providers: [
    // Angular 21 bootstraps NgModule applications zoneless by default. These
    // components are driven through @angular/elements, whose strategy only
    // re-enters the Angular zone when one exists, so without this the feed
    // renders its initial state and then never updates when a web service
    // call resolves. Do not remove without moving the components to signals.
    provideZoneChangeDetection(),
    provideAnimations(),
    provideHttpClient(),
  ],
  schemas: [CUSTOM_ELEMENTS_SCHEMA],
})
export class AppModule implements DoBootstrap {
  constructor(private injector: Injector) {
  }

  ngDoBootstrap() {
    // FeedComponent custom element.
    const feedCE = createCustomElement(FeedComponent, {injector: this.injector});
    customElements.define('snap-feed', feedCE);
    const modErrCE = createCustomElement(FeedErrorModalComponent, {injector: this.injector});
    customElements.define('feed-error-modal', modErrCE);
  }
}
