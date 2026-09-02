/** Keep inherited badges additive, but do not duplicate a direct badge. */
export function nestedOnly(direct, nested) {
  const directKinds = new Set(direct)
  return nested.filter((kind) => !directKinds.has(kind))
}
