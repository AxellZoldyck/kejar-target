import { cn } from "@/lib/utils";

export function Alert({ className, ...props }: React.ComponentProps<"div">) {
  return <div role="alert" className={cn("rounded-xl border bg-card p-4 text-sm leading-6", className)} {...props} />;
}
