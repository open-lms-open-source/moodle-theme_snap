import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { FeedErrorModalComponent } from './feed-error-modal.component';

describe('DefReportErrorModalComponent', () => {
  let component: FeedErrorModalComponent;
  let fixture: ComponentFixture<FeedErrorModalComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ FeedErrorModalComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(FeedErrorModalComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
