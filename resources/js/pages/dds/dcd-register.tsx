import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { CheckCircle, Loader2 } from 'lucide-react';

interface Props {
    flash?: {
        success?: string;
        error?: string;
    };
}

export default function DcdRegister({ flash }: Props) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        referral_code: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/api/dcd/create', {
            onSuccess: () => {
                reset();
            },
        });
    };

    return (
        <>
            <Head title="Register as Digital Content Distributor" />

            <div className="min-h-screen flex items-center justify-center bg-transparent py-12 px-4 sm:px-6 lg:px-8">
                <div className="max-w-md w-full space-y-8">
                    <div className="text-center">
                        <h1 className="text-3xl font-bold text-gray-900">Join Daya</h1>
                        <p className="mt-2 text-sm text-gray-600">
                            Become a Digital Content Distributor and start earning from campaigns
                        </p>
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle>Digital Content Distributor Registration</CardTitle>
                            <CardDescription>
                                Register with a referral code to get your QR code and start receiving campaigns
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {flash?.success && (
                                <Alert className="mb-4 border-green-200 bg-green-50">
                                    <CheckCircle className="h-4 w-4 text-green-600" />
                                    <AlertDescription className="text-green-800">
                                        {flash.success}
                                    </AlertDescription>
                                </Alert>
                            )}

                            {flash?.error && (
                                <Alert className="mb-4 border-red-200 bg-red-50">
                                    <AlertDescription className="text-red-800">
                                        {flash.error}
                                    </AlertDescription>
                                </Alert>
                            )}

                            <form onSubmit={submit} className="space-y-4">
                                <div>
                                    <Label htmlFor="name">Full Name</Label>
                                    <Input
                                        id="name"
                                        type="text"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        required
                                        placeholder="Enter your full name"
                                    />
                                    {errors.name && (
                                        <p className="mt-1 text-sm text-red-600">{errors.name}</p>
                                    )}
                                </div>

                                <div>
                                    <Label htmlFor="email">Email Address</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        value={data.email}
                                        onChange={(e) => setData('email', e.target.value)}
                                        required
                                        placeholder="Enter your email address"
                                    />
                                    {errors.email && (
                                        <p className="mt-1 text-sm text-red-600">{errors.email}</p>
                                    )}
                                </div>

                                <div>
                                    <Label htmlFor="referral_code">Referral Code</Label>
                                    <Input
                                        id="referral_code"
                                        type="text"
                                        value={data.referral_code}
                                        onChange={(e) => setData('referral_code', e.target.value)}
                                        required
                                        placeholder="Enter DA's referral code"
                                        className="uppercase"
                                    />
                                    {errors.referral_code && (
                                        <p className="mt-1 text-sm text-red-600">{errors.referral_code}</p>
                                    )}
                                    <p className="mt-1 text-xs text-gray-500">
                                        Get this code from a Digital Affiliate who invited you
                                    </p>
                                </div>

                                <Button
                                    type="submit"
                                    className="w-full"
                                    disabled={processing}
                                >
                                    {processing ? (
                                        <>
                                            <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                            Registering...
                                        </>
                                    ) : (
                                        'Register as Digital Content Distributor'
                                    )}
                                </Button>
                            </form>

                            <div className="mt-6 text-center text-sm text-gray-600">
                                <p className="mb-2">
                                    <strong>What you'll get:</strong>
                                </p>
                                <ul className="text-left space-y-1">
                                    <li>• Unique QR code for campaigns</li>
                                    <li>• Campaign assignments from clients</li>
                                    <li>• Earnings from completed campaigns</li>
                                    <li>• Monthly earnings reports</li>
                                </ul>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}