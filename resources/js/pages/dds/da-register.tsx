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

export default function DaRegister({ flash }: Props) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/api/da/create', {
            onSuccess: () => {
                reset();
            },
        });
    };

    return (
        <>
            <Head title="Register as Digital Affiliate" />

            <div className="min-h-screen flex items-center justify-center bg-transparent py-12 px-4 sm:px-6 lg:px-8">
                <div className="max-w-md w-full space-y-8">
                    <div className="text-center">
                        <h1 className="text-3xl font-bold text-gray-900">Join Daya</h1>
                        <p className="mt-2 text-sm text-gray-600">
                            Become a Digital Affiliate and start earning through referrals
                        </p>
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle>Digital Affiliate Registration</CardTitle>
                            <CardDescription>
                                Register to get your unique referral code and start building your network
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
                                        'Register as Digital Affiliate'
                                    )}
                                </Button>
                            </form>

                            <div className="mt-6 text-center text-sm text-gray-600">
                                <p className="mb-2">
                                    <strong>What you'll get:</strong>
                                </p>
                                <ul className="text-left space-y-1">
                                    <li>• Unique referral code</li>
                                    <li>• QR code for sharing</li>
                                    <li>• 10% commission on all referrals</li>
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