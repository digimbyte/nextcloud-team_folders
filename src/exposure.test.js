import { describe, expect, it } from 'vitest'
import { nestedOnly } from './exposure.js'

describe('nestedOnly', () => {
  it('keeps descendant-only exposure kinds', () => {
    expect(nestedOnly(['people'], ['people', 'link', 'public'])).toEqual(['link', 'public'])
  })

  it('preserves additive independent states', () => {
    expect(nestedOnly([], ['people', 'federated'])).toEqual(['people', 'federated'])
  })
})
