import { TestBed } from '@angular/core/testing';

import { MoodleService } from './moodle.service';

describe('MoodleService', () => {
  beforeEach(() => TestBed.configureTestingModule({}));

  it('should be created', () => {
    const service: MoodleService = TestBed.get(MoodleService);
    expect(service).toBeTruthy();
  });
});
