"use client";

import { zodResolver } from "@hookform/resolvers/zod";
import { LoaderCircle } from "lucide-react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useForm } from "react-hook-form";
import { toast } from "sonner";
import { z } from "zod";

import { Alert } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { authApi } from "@/lib/api/endpoints/auth";
import { isApiError } from "@/lib/api/api-error";
import { roleHome } from "@/lib/auth/role-home";

const loginSchema = z.object({
  email: z.string().email("Masukkan email yang valid."),
  password: z.string().min(1, "Password wajib diisi."),
});

const registerSchema = z.object({
  company_name: z.string().min(2, "Nama perusahaan minimal 2 karakter."),
  team_name: z.string().min(2, "Nama tim minimal 2 karakter."),
  name: z.string().min(2, "Nama minimal 2 karakter."),
  email: z.string().email("Masukkan email yang valid."),
  password: z.string().min(8, "Password minimal 8 karakter."),
  password_confirmation: z.string(),
  timezone: z.string(),
}).refine((value) => value.password === value.password_confirmation, {
  message: "Konfirmasi password tidak sama.",
  path: ["password_confirmation"],
});

type LoginValues = z.infer<typeof loginSchema>;
type RegisterValues = z.infer<typeof registerSchema>;

function FieldError({ message }: { message?: string }) {
  return message ? <p className="text-xs text-destructive">{message}</p> : null;
}

export function LoginForm() {
  const router = useRouter();
  const form = useForm<LoginValues>({ resolver: zodResolver(loginSchema), defaultValues: { email: "", password: "" } });

  const submit = form.handleSubmit(async (values) => {
    try {
      const result = await authApi.login(values);
      toast.success("Selamat datang kembali.");
      router.replace(roleHome[result.user.role]);
      router.refresh();
    } catch (error) {
      form.setError("root", { message: isApiError(error) ? error.message : "Login gagal. Coba lagi." });
    }
  });

  return (
    <form className="space-y-5" onSubmit={submit} noValidate>
      {form.formState.errors.root?.message && <Alert className="border-destructive/25 bg-destructive/5 text-destructive">{form.formState.errors.root.message}</Alert>}
      <div className="space-y-2"><Label htmlFor="email">Email</Label><Input id="email" type="email" autoComplete="email" placeholder="nama@perusahaan.com" {...form.register("email")} /><FieldError message={form.formState.errors.email?.message} /></div>
      <div className="space-y-2"><Label htmlFor="password">Password</Label><Input id="password" type="password" autoComplete="current-password" {...form.register("password")} /><FieldError message={form.formState.errors.password?.message} /></div>
      <Button className="w-full" size="lg" disabled={form.formState.isSubmitting}>{form.formState.isSubmitting && <LoaderCircle className="animate-spin" />}Masuk</Button>
      <p className="text-center text-sm text-muted-foreground">Belum punya akun? <Link className="font-semibold text-primary hover:underline" href="/register">Mulai trial 3 hari</Link></p>
    </form>
  );
}

export function RegisterForm() {
  const router = useRouter();
  const form = useForm<RegisterValues>({
    resolver: zodResolver(registerSchema),
    defaultValues: { company_name: "", team_name: "Tim Utama", name: "", email: "", password: "", password_confirmation: "", timezone: "Asia/Jakarta" },
  });

  const submit = form.handleSubmit(async (values) => {
    try {
      const result = await authApi.register(values);
      toast.success("Workspace berhasil dibuat. Trial 3 hari dimulai.");
      router.replace(roleHome[result.user.role]);
      router.refresh();
    } catch (error) {
      if (isApiError(error)) {
        Object.entries(error.errors).forEach(([field, messages]) => form.setError(field as keyof RegisterValues, { message: messages[0] }));
        form.setError("root", { message: error.message });
      } else form.setError("root", { message: "Registrasi gagal. Coba lagi." });
    }
  });

  return (
    <form className="space-y-4" onSubmit={submit} noValidate>
      {form.formState.errors.root?.message && <Alert className="border-destructive/25 bg-destructive/5 text-destructive">{form.formState.errors.root.message}</Alert>}
      <div className="grid gap-4 sm:grid-cols-2">
        <div className="space-y-2 sm:col-span-2"><Label htmlFor="company_name">Nama perusahaan</Label><Input id="company_name" placeholder="PT Contoh Indonesia" {...form.register("company_name")} /><FieldError message={form.formState.errors.company_name?.message} /></div>
        <div className="space-y-2"><Label htmlFor="team_name">Nama tim awal</Label><Input id="team_name" {...form.register("team_name")} /><FieldError message={form.formState.errors.team_name?.message} /></div>
        <div className="space-y-2"><Label htmlFor="name">Nama supervisor</Label><Input id="name" autoComplete="name" {...form.register("name")} /><FieldError message={form.formState.errors.name?.message} /></div>
        <div className="space-y-2 sm:col-span-2"><Label htmlFor="email">Email kerja</Label><Input id="email" type="email" autoComplete="email" {...form.register("email")} /><FieldError message={form.formState.errors.email?.message} /></div>
        <div className="space-y-2"><Label htmlFor="password">Password</Label><Input id="password" type="password" autoComplete="new-password" {...form.register("password")} /><FieldError message={form.formState.errors.password?.message} /></div>
        <div className="space-y-2"><Label htmlFor="password_confirmation">Ulangi password</Label><Input id="password_confirmation" type="password" autoComplete="new-password" {...form.register("password_confirmation")} /><FieldError message={form.formState.errors.password_confirmation?.message} /></div>
      </div>
      <input type="hidden" {...form.register("timezone")} />
      <Button className="w-full" size="lg" disabled={form.formState.isSubmitting}>{form.formState.isSubmitting && <LoaderCircle className="animate-spin" />}Buat workspace gratis</Button>
      <p className="text-center text-xs leading-5 text-muted-foreground">Dengan mendaftar, Anda memulai trial Starter selama 3 hari. Tidak perlu kartu kredit.</p>
    </form>
  );
}
