import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Users, UserCheck, FileText } from 'lucide-react';

export default function Welcome() {

    return (
        <>
            <Head title="Welcome">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link
                    href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600"
                    rel="stylesheet"
                />
            </Head>
            <div className="h-screen bg-background text-foreground overflow-y-auto bg-white dark:from-slate-700 dark:via-slate-800 dark:to-slate-700">

                <div className="flex min-h-screen flex-col items-center bg-transparent p-6  text-slate-900 lg:justify-center lg:p-8 relative z-10">
                    {/* Logo Header */}
                    <header className="mb-8 w-full max-w-4xl flex justify-center">
                        <div className="flex items-center gap-3">
                            <img 
                                src="/logo.png" 
                                alt="Daya Logo" 
                                className="h-12 w-12 object-contain"
                            />
                            <h1 className="text-3xl font-bold text-slate-900 dark:text-white">
                                Daya
                            </h1>
                        </div>
                    </header>

                    <main className="w-full max-w-4xl">
                        <div className="w-full">
                            <h2 className="mb-4 text-2xl font-semibold text-slate-900 dark:text-white text-center">Welcome</h2>
                            <p className="mb-6 text-sm text-slate-900 dark:text-slate-300 text-center">
                                Join our Digital Distribution System - Choose your role to get started
                            </p>

                            <div className="grid gap-6 md:grid-cols-3 mb-8">
                                <Link href={`/da/register${window.location.search}`}>
                                    <Card className="cursor-pointer hover:shadow-2xl transition-all duration-300 bg-white/10 backdrop-blur-md border-white/20 hover:bg-white/20 hover:scale-105 dark:bg-slate-800/50 dark:border-slate-600 dark:hover:bg-slate-700/50">
                                        <CardHeader className="text-center">
                                            <Users className="mx-auto h-12 w-12 text-blue-300 mb-2" />
                                            <CardTitle className="text-lg text-slate-900 dark:text-white">Digital Ambassador</CardTitle>
                                            <CardDescription className="text-slate-900 dark:text-slate-300">
                                                Earn commissions by referring Digital Content Distributors
                                            </CardDescription>
                                        </CardHeader>
                                        <CardContent className="text-center">
                                            <Button className="w-full bg-gradient-to-r from-blue-500 to-cyan-500 hover:from-blue-600 hover:to-cyan-600 text-white border-0 shadow-lg hover:shadow-xl transition-all duration-300">
                                                Register as DA
                                            </Button>
                                        </CardContent>
                                    </Card>
                                </Link>

                                <Link href={`/dcd/register${window.location.search}`}>
                                    <Card className="cursor-pointer hover:shadow-2xl transition-all duration-300 bg-white/10 backdrop-blur-md border-white/20 hover:bg-white/20 hover:scale-105 dark:bg-slate-800/50 dark:border-slate-600 dark:hover:bg-slate-700/50">
                                        <CardHeader className="text-center">
                                            <UserCheck className="mx-auto h-12 w-12 text-green-300 mb-2" />
                                            <CardTitle className="text-lg text-slate-900 dark:text-white">Digital Content Distributor</CardTitle>
                                            <CardDescription className="text-slate-900 dark:text-slate-300">
                                                Execute campaigns and earn from client projects
                                            </CardDescription>
                                        </CardHeader>
                                        <CardContent className="text-center">
                                            <Button className="w-full bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-600 hover:to-emerald-600 text-white border-0 shadow-lg hover:shadow-xl transition-all duration-300">
                                                Register as DCD
                                            </Button>
                                        </CardContent>
                                    </Card>
                                </Link>

                                <Link href={`/campaign/submit${window.location.search}`}>
                                    <Card className="cursor-pointer hover:shadow-2xl transition-all duration-300 bg-white/10 backdrop-blur-md border-white/20 hover:bg-white/20 hover:scale-105 dark:bg-slate-800/50 dark:border-slate-600 dark:hover:bg-slate-700/50">
                                        <CardHeader className="text-center">
                                            <FileText className="mx-auto h-12 w-12 text-purple-300 mb-2" />
                                            <CardTitle className="text-lg text-slate-900 dark:text-white">Submit Campaign</CardTitle>
                                            <CardDescription className="text-slate-900 dark:text-slate-300">
                                                Launch your campaign with our network of distributors
                                            </CardDescription>
                                        </CardHeader>
                                        <CardContent className="text-center">
                                            <Button className="w-full bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600 text-white border-0 shadow-lg hover:shadow-xl transition-all duration-300">
                                                Submit Campaign
                                            </Button>
                                        </CardContent>
                                    </Card>
                                </Link>
                            </div>
                        </div>
                    </main>
                </div >
            </div >
        </>
    );
}