import { describe, expect, it } from 'vitest'
import { evaluateHealth } from './health.js'

describe('runtime UI health', () => {
  it('passes when the API, rows, bases, and overlays are present', () => {
    expect(evaluateHealth({ apiStatus: 'ready', itemCount: 4, matchedRows: 4, expectedBases: 4, actualBases: 4, expectedOverlays: 3, actualOverlays: 3 }).ok).toBe(true)
  })

  it('fails closed when exposure overlays are missing', () => {
    const result = evaluateHealth({ apiStatus: 'ready', itemCount: 4, matchedRows: 4, expectedBases: 4, actualBases: 4, expectedOverlays: 3, actualOverlays: 0 })
    expect(result.ok).toBe(false)
    expect(result.checks.overlays).toBe(false)
  })
})
