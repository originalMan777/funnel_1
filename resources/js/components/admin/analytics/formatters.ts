export const numberFormatter = new Intl.NumberFormat(undefined, {
  maximumFractionDigits: 0,
})

export const decimalFormatter = new Intl.NumberFormat(undefined, {
  minimumFractionDigits: 0,
  maximumFractionDigits: 2,
})

export const compactNumberFormatter = new Intl.NumberFormat(undefined, {
  notation: 'compact',
  maximumFractionDigits: 1,
})

export function formatNumber(value: number | null | undefined): string {
  return numberFormatter.format(value ?? 0)
}

export function formatPercent(value: number | null | undefined): string {
  if (value === null || value === undefined) {
    return '—'
  }

  return `${decimalFormatter.format(value)}%`
}

export function formatCompactNumber(value: number | null | undefined): string {
  return compactNumberFormatter.format(value ?? 0)
}

export function formatDateTime(value: string | null | undefined): string {
  if (!value) {
    return '—'
  }

  return new Date(value).toLocaleString()
}

export function formatDuration(value: number | null | undefined): string {
  if (value === null || value === undefined) {
    return '—'
  }

  const totalSeconds = Math.max(Math.round(value), 0)

  if (totalSeconds < 60) {
    return `${totalSeconds}s`
  }

  const hours = Math.floor(totalSeconds / 3600)
  const minutes = Math.floor((totalSeconds % 3600) / 60)
  const seconds = totalSeconds % 60

  if (hours > 0) {
    return `${hours}h ${minutes}m`
  }

  if (seconds === 0) {
    return `${minutes}m`
  }

  return `${minutes}m ${seconds}s`
}
