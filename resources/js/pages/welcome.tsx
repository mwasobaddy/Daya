import { dashboard, login, register } from '@/routes';
import { type SharedData } from '@/types';
import React, { useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import RoleSelector from '@/components/RoleSelector';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Users, UserCheck, FileText } from 'lucide-react';

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
            <div className="flex min-h-screen flex-col items-center bg-[#FDFDFC] p-6 text-[#1b1b18] lg:justify-center lg:p-8 dark:bg-[#0a0a0a]">
                <header className="mb-6 w-full max-w-[335px] text-sm not-has-[nav]:hidden lg:max-w-4xl">
                    <nav className="flex items-center justify-end gap-4">
                        {auth.user ? (
                            <Link
                                href={dashboard()}
                                className="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                            >
                                Dashboard
                            </Link>
                        ) : (
                            <>
                                <Link
                                    href={login()}
                                    className="inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]"
                                >
                                    Log in
                                </Link>
                                {canRegister && (
                                    <Link
                                        href={register()}
                                        className="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
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
                        <h1 className="mb-4 text-2xl font-semibold text-[color:var(--foreground)]">Welcome to Daya</h1>
                        <p className="mb-6 text-sm text-[color:var(--muted-foreground)]">
                            Join our Digital Distribution System - Choose your role to get started
                        </p>

                        <div className="grid gap-6 md:grid-cols-3 mb-8">
                            <Card className="cursor-pointer hover:shadow-lg transition-shadow">
                                <CardHeader className="text-center">
                                    <Users className="mx-auto h-12 w-12 text-blue-600 mb-2" />
                                    <CardTitle className="text-lg">Digital Affiliate</CardTitle>
                                    <CardDescription>
                                        Earn commissions by referring Digital Content Distributors
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="text-center">
                                    <Link href={`/da/register${window.location.search}`}>
                                        <Button className="w-full">
                                            Register as DA
                                        </Button>
                                    </Link>
                                </CardContent>
                            </Card>

                            <Card className="cursor-pointer hover:shadow-lg transition-shadow">
                                <CardHeader className="text-center">
                                    <UserCheck className="mx-auto h-12 w-12 text-green-600 mb-2" />
                                    <CardTitle className="text-lg">Digital Content Distributor</CardTitle>
                                    <CardDescription>
                                        Execute campaigns and earn from client projects
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="text-center">
                                    <Link href={`/dcd/register${window.location.search}`}>
                                        <Button className="w-full">
                                            Register as DCD
                                        </Button>
                                    </Link>
                                </CardContent>
                            </Card>

                            <Card className="cursor-pointer hover:shadow-lg transition-shadow">
                                <CardHeader className="text-center">
                                    <FileText className="mx-auto h-12 w-12 text-purple-600 mb-2" />
                                    <CardTitle className="text-lg">Submit Campaign</CardTitle>
                                    <CardDescription>
                                        Launch your campaign with our network of distributors
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="text-center">
                                    <Link href={`/campaign/submit${window.location.search}`}>
                                        <Button className="w-full">
                                            Submit Campaign
                                        </Button>
                                    </Link>
                                </CardContent>
                            </Card>
                        </div>

                        {/* <div className="text-center">
                            <p className="text-sm text-[color:var(--muted-foreground)] mb-4">
                                Already have an account? Sign in to access your dashboard.
                            </p>
                            <RoleSelector value={role} onChange={(r) => setRole(r)} />
                            <div className="mt-4 text-sm text-[color:var(--foreground)]">
                                Selected role: <strong>{role ?? 'none'}</strong>
                            </div>
                        </div> */}
                    </div>
                </main>
            </div>
        </>
    );
}
