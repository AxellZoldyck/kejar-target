import { Skeleton } from "@/components/ui/skeleton";

export default function RootLoading() {
  return (
    <main className="mx-auto w-full max-w-7xl space-y-6 px-4 py-8 md:px-6">
      <Skeleton className="h-10 w-56" />
      <div className="grid gap-4 md:grid-cols-3">
        <Skeleton className="h-36" />
        <Skeleton className="h-36" />
        <Skeleton className="h-36" />
      </div>
    </main>
  );
}
