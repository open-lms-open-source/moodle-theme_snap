import { ComponentFixture, TestBed, waitForAsync } from '@angular/core/testing';

import { FeedComponent } from './feed.component';
import {provideHttpClientTesting} from "@angular/common/http/testing";
import {provideHttpClient} from "@angular/common/http";
import {provideAnimations} from "@angular/platform-browser/animations";
import {MoodleStringPipe, StringService} from "openlms-angular-lib";
import {MockStringService} from "../string.service.spec";

describe('FeedComponent', () => {
  let component: FeedComponent;
  let fixture: ComponentFixture<FeedComponent>;

  beforeEach(waitForAsync(() => {
    TestBed.configureTestingModule({
      declarations: [ FeedComponent ],
      imports: [MoodleStringPipe],
      providers: [
        provideAnimations(),
        provideHttpClient(),
        provideHttpClientTesting(),
        {
          provide: StringService,
          useClass: MockStringService
        }
      ],
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(FeedComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
