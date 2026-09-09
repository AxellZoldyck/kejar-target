import { NextRequest, NextResponse } from "next/server";

const protectedPrefixes = ["/admin", "/spv", "/sales"];

export function proxy(request: NextRequest) {
  const protectedRoute = protectedPrefixes.some((prefix) => request.nextUrl.pathname === prefix || request.nextUrl.pathname.startsWith(`${prefix}/`));
  if (!protectedRoute) return NextResponse.next();

  const cookieName = process.env.SESSION_COOKIE_NAME || "kejar_target_session";
  console.info("[auth/proxy]", {
  path: request.nextUrl.pathname,
  hasSessionCookie: request.cookies.has(cookieName),
});
if (!request.cookies.has(cookieName)) {
    const login = new URL("/login", request.url);
    login.searchParams.set("next", `${request.nextUrl.pathname}${request.nextUrl.search}`);
    return NextResponse.redirect(login);
  }

  return NextResponse.next();
}

export const config = { matcher: ["/admin/:path*", "/spv/:path*", "/sales/:path*"] };
