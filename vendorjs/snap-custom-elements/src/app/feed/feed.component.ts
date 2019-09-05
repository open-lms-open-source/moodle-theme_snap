import {Component, OnInit, ViewEncapsulation, Input, HostListener} from '@angular/core';

import {FeedService} from '../feed.service';
import {FeedItem} from "../feed-item";
import {animate, query, stagger, style, transition, trigger} from "@angular/animations";

@Component({
  selector: 'snap-feed',
  template: `
      <h2>{{ title }}</h2>
      <div id="{{ elemId }}" [@growIn]="feedItemTotal">
          <div class="snap-media-object feeditem {{feedItem.extraClasses}}" *ngFor="let feedItem of feedItems">
              <img *ngIf="feedItem.iconUrl !== ''" src="{{feedItem.iconUrl}}" alt="{{feedItem.iconDesc}}" [className]="feedItem.iconClass">
              <div class="snap-media-body">
                  <a href="{{feedItem.actionUrl}}">
                      <h3>
                          {{feedItem.title}}
                          <small>
                              <br>
                              {{feedItem.subTitle}}
                          </small>
                      </h3>
                  </a>
                  <span *ngIf="feedItem.description" class="snap-media-meta" [innerHTML]="feedItem.description">
                  </span>
              </div>
          </div>
          <p class="small" *ngIf="feedItemTotal == 0">{{emptyMessage}}</p>
      </div>
      <a *ngIf="viewMoreEnabled && nextPage >= 0" [attr.href]="'#'" class="snap-personal-menu-more"
         (click)="getFeed($event)">
          <small>{{viewMoreMessage}}</small>
      </a>
      <a *ngIf="nextPage === -1 && showReload" [attr.href]="'#'" class="snap-personal-menu-more"
         (click)="resetFeed($event)">
          <small>{{reloadMessage}}</small>
      </a>
  `,
  animations: [
    trigger('growIn', [
      transition(':increment', [
        query(':enter', [
          style({opacity: 0, transform: 'translateY(-100px)', height: 0}),
          stagger(100, [
            animate('500ms cubic-bezier(0.35, 0, 0.25, 1)',
              style({opacity: 1, transform: 'none',  height: '*'}))
          ])
        ], {optional: true})
      ]),
      transition(':decrement', [
        query(':leave', [
          stagger(50, [
            animate('300ms ease-out', style({ opacity: 0, height: '0px' })),
          ]),
        ], {optional: true})
      ])
    ])
  ],
  styles: [],
  encapsulation: ViewEncapsulation.None
})
export class FeedComponent implements OnInit {
  @Input() elemId: string;
  @Input() title: string;
  @Input() feedId: string;
  @Input() sessKey: string;
  @Input() showReload?: boolean;
  @Input() virtualPaging?: boolean;
  @Input() pageSize: number;
  @Input() emptyMessage: string;
  @Input() viewMoreMessage: string;
  @Input() reloadMessage: string;

  nextPage: number;
  feedItems: FeedItem[];
  feedItemTotal: number;
  resetInProgress: boolean;
  viewMoreEnabled: boolean;

  private feedItemCache: FeedItem[];

  constructor(private feedService: FeedService) {
  }

  ngOnInit() {
    this.feedItems = [];
    if (document.querySelectorAll('body.snap-pm-open').length > 0) {
      this.resetFeed();
    }
  }

  getFeed(event?: Event): void {
    this.viewMoreEnabled = false;

    if (event) {
      event.preventDefault();
    }

    if (this.processVirtualPaging()) {
      this.viewMoreEnabled = true;
      return;
    }

    this.feedService.getFeed(this.sessKey, this.feedId, this.nextPage, this.pageSize)
      .subscribe(feedResponse => {
        if (feedResponse[0].error) {
          return;
        }

        const data: FeedItem[] = feedResponse[0].data;

        if (this.virtualPaging && data.length > 0) {
          this.feedItemCache = data;
          this.nextPage = 1;
          this.processVirtualPaging();
        } else {
          this.processNextPage(data);
        }

        this.viewMoreEnabled = true;
      });
  }

  processVirtualPaging() : boolean {
    if (!this.virtualPaging || this.feedItemCache.length === 0) {
      return false;
    }

    if (this.nextPage < 0) {
      return true;
    }

    const totalItems: number = this.feedItemCache.length;
    const nextStartIdx: number = this.pageSize * (this.nextPage - 1);
    const lastIdx: number = totalItems;
    if (nextStartIdx <= lastIdx) {
      let start: number = nextStartIdx,
          end: number = nextStartIdx + this.pageSize;
      end = end > lastIdx ? lastIdx : end;
      const newFeedItems: FeedItem[] = this.feedItemCache.slice(start, end);
      this.processNextPage(newFeedItems);
    }

    return true;
  }

  processNextPage(data: FeedItem[]) {
    if (this.resetInProgress) {
      this.feedItems = [];
      this.resetInProgress = false;
    }

    this.feedItems = this.feedItems.concat(data);
    this.feedItemTotal = this.feedItems.length;
    if (data.length == this.pageSize) {
      this.nextPage++;
    } else {
      this.nextPage = -1;
    }
  }

  @HostListener('document:snapPersonalMenuOpen', ['$event'])
  resetFeed(event?: Event): void {
    if (event) {
      event.preventDefault();
    }

    this.nextPage = 0;
    this.feedItemCache = [];
    this.resetInProgress = true;

    this.getFeed();
  }
}
