import { dashboard, login, register } from '@/routes';
import { type SharedData } from '@/types';
import React, { useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import RoleSelector from '@/components/RoleSelector';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Users, UserCheck, FileText } from 'lucide-react';
import AppearanceToggleDropdown from '@/components/appearance-dropdown';

export default function Welcome({
    canRegister = true,
}: {
    canRegister?: boolean;
}) {
    const { auth } = usePage<SharedData>().props;
    const [role, setRole] = useState<'client' | 'dcd' | 'da' | undefined>(undefined);

    return (
        <>
            <Head title="Welcome">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link
                    href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600"
                    rel="stylesheet"
                />
            </Head>
            <div className="h-screen bg-background text-foreground overflow-y-auto bg-gradient-to-r from-blue-300 via-indigo-400 to-purple-300 text-white dark:from-slate-700 dark:via-slate-800 dark:to-slate-700">
                {/* Appearance Toggle */}
                <div className="absolute top-4 right-4 z-50">
                    <AppearanceToggleDropdown />
                </div>

                <div className="absolute inset-0 bg-black opacity-10"></div>
                <div className="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxwYXRoIGQ9Ik0zNiAxOGMzLjMxNCAwIDYgMi42ODYgNiA2cy0yLjY4NiA2LTYgNi02LTIuNjg2LTYtNiAyLjY4Ni02IDYtNiIgc3Ryb2tlPSIjZmZmIiBzdHJva2Utd2lkdGg9IjIiIG9wYWNpdHk9Ii4xIi8+PC9nPjwvc3ZnPg==')] opacity-100 dark:opacity-80"></div>

                <div className="flex min-h-screen flex-col items-center bg-transparent p-6 text-white lg:justify-center lg:p-8 relative z-10">
                    <header className="mb-6 w-full max-w-[335px] not-has-[nav]:hidden lg:max-w-4xl">
                        <nav className="flex items-center justify-end gap-4">
                            {auth.user ? (
                                <Link
                                    href={dashboard()}
                                    className="inline-block rounded-sm border border-white/20 px-5 py-1.5 text-sm leading-normal text-white hover:border-white/40 backdrop-blur-sm bg-white/10 hover:bg-white/20 transition-all duration-200"
                                >
                                    Dashboard
                                </Link>
                            ) : (
                                <>
                                    <Link
                                        href={login()}
                                        className="inline-block rounded-sm border border-white/20 px-5 py-1.5 text-sm leading-normal text-white hover:border-white/40 backdrop-blur-sm bg-white/10 hover:bg-white/20 transition-all duration-200"
                                    >
                                        Log in
                                    </Link>
                                    {canRegister && (
                                        <Link
                                            href={register()}
                                            className="inline-block rounded-sm border border-white/20 px-5 py-1.5 text-sm leading-normal text-white hover:border-white/40 backdrop-blur-sm bg-white/10 hover:bg-white/20 transition-all duration-200"
                                        >
                                            Register
                                        </Link>
                                    )}
                                </>
                            )}
                        </nav>
                    </header>
                    <main className="w-full max-w-4xl">
                        <div className="w-full">
                            <h1 className="mb-4 text-2xl font-semibold text-white">Welcome to Daya</h1>
                            <p className="mb-6 text-sm text-blue-100 dark:text-slate-300">
                                Join our Digital Distribution System - Choose your role to get started
                            </p>

                            <div className="grid gap-6 md:grid-cols-3 mb-8">
                                <Card className="cursor-pointer hover:shadow-2xl transition-all duration-300 bg-white/10 backdrop-blur-md border-white/20 hover:bg-white/20 hover:scale-105 dark:bg-slate-800/50 dark:border-slate-600 dark:hover:bg-slate-700/50">
                                    <CardHeader className="text-center">
                                        <Users className="mx-auto h-12 w-12 text-blue-300 mb-2" />
                                        <CardTitle className="text-lg text-white">Digital Affiliate</CardTitle>
                                        <CardDescription className="text-blue-100">
                                            Earn commissions by referring Digital Content Distributors
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="text-center">
                                        <Link href={`/da/register${window.location.search}`}>
                                            <Button className="w-full bg-gradient-to-r from-blue-500 to-cyan-500 hover:from-blue-600 hover:to-cyan-600 text-white border-0 shadow-lg hover:shadow-xl transition-all duration-300">
                                                Register as DA
                                            </Button>
                                        </Link>
                                    </CardContent>
                                </Card>

                                <Card className="cursor-pointer hover:shadow-2xl transition-all duration-300 bg-white/10 backdrop-blur-md border-white/20 hover:bg-white/20 hover:scale-105 dark:bg-slate-800/50 dark:border-slate-600 dark:hover:bg-slate-700/50">
                                    <CardHeader className="text-center">
                                        <UserCheck className="mx-auto h-12 w-12 text-green-300 mb-2" />
                                        <CardTitle className="text-lg text-white">Digital Content Distributor</CardTitle>
                                        <CardDescription className="text-blue-100">
                                            Execute campaigns and earn from client projects
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="text-center">
                                        <Link href={`/dcd/register${window.location.search}`}>
                                            <Button className="w-full bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-600 hover:to-emerald-600 text-white border-0 shadow-lg hover:shadow-xl transition-all duration-300">
                                                Register as DCD
                                            </Button>
                                        </Link>
                                    </CardContent>
                                </Card>

                                <Card className="cursor-pointer hover:shadow-2xl transition-all duration-300 bg-white/10 backdrop-blur-md border-white/20 hover:bg-white/20 hover:scale-105 dark:bg-slate-800/50 dark:border-slate-600 dark:hover:bg-slate-700/50">
                                    <CardHeader className="text-center">
                                        <FileText className="mx-auto h-12 w-12 text-purple-300 mb-2" />
                                        <CardTitle className="text-lg text-white">Submit Campaign</CardTitle>
                                        <CardDescription className="text-blue-100">
                                            Launch your campaign with our network of distributors
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="text-center">
                                        <Link href={`/campaign/submit${window.location.search}`}>
                                            <Button className="w-full bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600 text-white border-0 shadow-lg hover:shadow-xl transition-all duration-300">
                                                Submit Campaign
                                            </Button>
                                        </Link>
                                    </CardContent>
                                </Card>
                            </div>

                            {/* <div className="text-center">
                                <p className="text-sm text-blue-100 mb-4">
                                    Already have an account? Sign in to access your dashboard.
                                </p>
                                <RoleSelector value={role} onChange={(r) => setRole(r)} />
                                <div className="mt-4 text-sm text-white">
                                    Selected role: <strong>{role ?? 'none'}</strong>
                                </div>
                            </div> */}
                        </div>
                    </main>
                </div>
            </div>
        </>
    );
}