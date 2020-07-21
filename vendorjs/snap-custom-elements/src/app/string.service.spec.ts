import { TestBed } from '@angular/core/testing';

import { StringService } from './string.service';

describe('StringService', () => {
  beforeEach(() => TestBed.configureTestingModule({}));

  it('should be created', () => {
    const service: StringService = TestBed.get(StringService);
    expect(service).toBeTruthy();
  });
});
