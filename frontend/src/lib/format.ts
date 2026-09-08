const defaultTimezone = "Asia/Jakarta";
const dateOnlyPattern = /^\d{4}-\d{2}-\d{2}$/;

function calendarParts(timezone: string, date: Date) {
  const parts = new Intl.DateTimeFormat("en-CA-u-ca-gregory-nu-latn", {
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
    timeZone: timezone,
  }).formatToParts(date);
  const value = (type: Intl.DateTimeFormatPartTypes) =>
    parts.find((part) => part.type === type)?.value ?? "";

  return {
    year: value("year"),
    month: value("month"),
    day: value("day"),
  };
}

export function currentCalendarDate(
  timezone = defaultTimezone,
  date = new Date(),
) {
  const parts = calendarParts(timezone, date);
  return `${parts.year}-${parts.month}-${parts.day}`;
}

export function currentCalendarMonth(
  timezone = defaultTimezone,
  date = new Date(),
) {
  return currentCalendarDate(timezone, date).slice(0, 7);
}

export function formatRupiah(value: number | null | undefined) {
  if (value == null) return "Belum tersedia";
  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    maximumFractionDigits: 0,
  }).format(value);
}

export function formatNumber(value: number | null | undefined) {
  if (value == null) return "—";
  return new Intl.NumberFormat("id-ID").format(value);
}

export function formatPercent(value: number | null | undefined) {
  if (value == null) return "Belum diatur";
  return `${new Intl.NumberFormat("id-ID", { maximumFractionDigits: 1 }).format(value)}%`;
}

export function formatDate(
  value: string | null | undefined,
  timezone = defaultTimezone,
) {
  if (!value) return "—";

  // A YYYY-MM-DD value is already a calendar date, not a UTC instant. Format
  // it at UTC midnight so viewers west of UTC never see the previous day.
  const isDateOnly = dateOnlyPattern.test(value);
  const date = new Date(isDateOnly ? `${value}T00:00:00Z` : value);
  if (Number.isNaN(date.getTime())) return "—";

  return new Intl.DateTimeFormat("id-ID", {
    dateStyle: "medium",
    timeZone: isDateOnly ? "UTC" : timezone,
  }).format(date);
}
