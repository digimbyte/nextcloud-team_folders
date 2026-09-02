export function evaluateHealth({ apiStatus, itemCount, matchedRows, expectedBases, actualBases, expectedOverlays, actualOverlays }) {
  const checks = {
    api: apiStatus === 'ready' || apiStatus === 'rebuilding',
    rows: itemCount === 0 || matchedRows > 0,
    bases: actualBases >= expectedBases,
    overlays: actualOverlays >= expectedOverlays,
  }
  return { ok: Object.values(checks).every(Boolean), checks }
}
