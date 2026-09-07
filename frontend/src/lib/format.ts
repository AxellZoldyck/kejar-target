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
  timezone = "Asia/Jakarta",
) {
  if (!value) return "—";
  return new Intl.DateTimeFormat("id-ID", {
    dateStyle: "medium",
    timeZone: timezone,
  }).format(new Date(value));
}
