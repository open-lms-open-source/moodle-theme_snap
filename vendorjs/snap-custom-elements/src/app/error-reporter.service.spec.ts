import { TestBed } from '@angular/core/testing';

import { ErrorReporterService } from './error-reporter.service';

describe('ErrorReporterService', () => {
  beforeEach(() => TestBed.configureTestingModule({}));

  it('should be created', () => {
    const service: ErrorReporterService = TestBed.get(ErrorReporterService);
    expect(service).toBeTruthy();
  });
});
