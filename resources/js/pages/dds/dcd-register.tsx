import React, { useState, useEffect, useRef } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { CheckCircle, Loader2, Shield, MapPin, Building, Clock, Music, Wallet, FileText } from 'lucide-react';
import { toast, ToastContainer } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';

interface Props {
    flash?: {
        success?: string;
        error?: string;
    };
}

interface Country {
    id: number;
    name: string;
    code: string;
}

interface County {
    id: number;
    name: string;
    country_id: number;
}

interface Subcounty {
    id: number;
    name: string;
    county_id: number;
}

declare global {
    interface Window {
        turnstile: {
            render: (element: string | HTMLElement, config: { sitekey: string; callback: (token: string) => void }) => void;
            remove: (element: string | HTMLElement) => void;
        };
    }
}

export default function DcdRegister({ flash }: Props) {
    const [countries, setCountries] = useState<Country[]>([]);
    const [counties, setCounties] = useState<County[]>([]);
    const [subcounties, setSubcounties] = useState<Subcounty[]>([]);
    const [countriesLoading, setCountriesLoading] = useState(true);
    const [countiesLoading, setCountiesLoading] = useState(false);
    const [subcountiesLoading, setSubcountiesLoading] = useState(false);
    const [turnstileToken, setTurnstileToken] = useState<string>('');
    const turnstileRef = useRef<HTMLDivElement>(null);

    const { data, setData, post, processing, errors, reset } = useForm({
        // Referral & Identification
        referral_code: '',
        full_name: '',
        national_id: '',
        dob: '',
        gender: '',
        email: '',
        country: '',
        county: '',
        subcounty: '',
        business_address: '',
        phone: '',
        latitude: '',
        longitude: '',

        // Business Information
        business_name: '',
        business_types: [] as string[],
        other_business_type: '',

        // Business Traffic & Hours
        daily_foot_traffic: '',
        operating_hours_start: '',
        operating_hours_end: '',
        operating_days: [] as string[],

        // Campaign Preferences
        campaign_types: [] as string[],

        // Music Preferences
        music_genres: [] as string[],
        other_music_genre: '',

        // Content Safety
        safe_for_kids: false,

        // Wallet Setup
        wallet_type: '',
        wallet_pin: '',
        confirm_pin: '',

        // Agreement
        terms: false,

        // Security
        turnstile_token: '',
    });

    useEffect(() => {
        const fetchCountries = async () => {
            try {
                const response = await fetch('/api/countries');
                const data = await response.json();
                setCountries(data);
            } catch (error) {
                console.error('Failed to fetch countries:', error);
            } finally {
                setCountriesLoading(false);
            }
        };

        fetchCountries();

        // Initialize Turnstile
        const script = document.createElement('script');
        script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js';
        script.async = true;
        script.defer = true;

        // Check if Turnstile script is already loaded
        if (!document.querySelector('script[src="https://challenges.cloudflare.com/turnstile/v0/api.js"]')) {
            document.head.appendChild(script);
        }

        // Wait for Turnstile to be available and render the widget
        const renderTurnstile = () => {
            if (window.turnstile && turnstileRef.current) {
                window.turnstile.render(turnstileRef.current, {
                    sitekey: '1x00000000000000000000AA',
                    callback: (token: string) => {
                        setTurnstileToken(token);
                        setData('turnstile_token', token);
                    },
                });
            } else {
                // Retry after a short delay if not ready
                setTimeout(renderTurnstile, 100);
            }
        };

        renderTurnstile();

        return () => {
            if (window.turnstile && turnstileRef.current) {
                window.turnstile.remove(turnstileRef.current);
            }
        };
    }, []);

    const fetchCounties = async (countryId: number) => {
        setCountiesLoading(true);
        try {
            const response = await fetch(`/api/countries/${countryId}/counties`);
            const data = await response.json();
            setCounties(data);
        } catch (error) {
            console.error('Failed to fetch counties:', error);
        } finally {
            setCountiesLoading(false);
        }
    };

    const fetchSubcounties = async (countyId: number) => {
        setSubcountiesLoading(true);
        try {
            const response = await fetch(`/api/counties/${countyId}/subcounties`);
            const data = await response.json();
            setSubcounties(data);
        } catch (error) {
            console.error('Failed to fetch subcounties:', error);
        } finally {
            setSubcountiesLoading(false);
        }
    };

    const handleBusinessTypeChange = (type: string, checked: boolean) => {
        if (checked) {
            setData('business_types', [...data.business_types, type]);
        } else {
            setData('business_types', data.business_types.filter(t => t !== type));
        }
    };

    const handleCampaignTypeChange = (type: string, checked: boolean) => {
        if (checked) {
            setData('campaign_types', [...data.campaign_types, type]);
        } else {
            setData('campaign_types', data.campaign_types.filter(t => t !== type));
        }
    };

    const handleMusicGenreChange = (genre: string, checked: boolean) => {
        if (checked) {
            setData('music_genres', [...data.music_genres, genre]);
        } else {
            setData('music_genres', data.music_genres.filter(g => g !== genre));
        }
    };

    const handleOperatingDayChange = (day: string, checked: boolean) => {
        if (checked) {
            setData('operating_days', [...data.operating_days, day]);
        } else {
            setData('operating_days', data.operating_days.filter(d => d !== day));
        }
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        // Validate required fields
        if (!turnstileToken) {
            toast.error('Please complete the security verification');
            return;
        }

        if (data.wallet_pin !== data.confirm_pin) {
            toast.error('Wallet PINs do not match');
            return;
        }

        post('/api/dcd/create', {
            onSuccess: () => {
                reset();
                setTurnstileToken('');
                toast.success('Registration successful! Check your email for your QR code.');
            },
            onError: (errors: any) => {
                toast.error('Registration failed. Please check the form and try again.');
            },
        });
    };

    const businessTypes = [
        // Retail & Merchant Outlets
        { value: 'kiosk_duka', label: 'Kiosk/Duka', category: 'retail' },
        { value: 'mini_supermarket', label: 'Mini Supermarket', category: 'retail' },
        { value: 'wholesale_shop', label: 'Wholesale Shop', category: 'retail' },
        { value: 'hardware_store', label: 'Hardware Store', category: 'retail' },
        { value: 'agrovet', label: 'Agrovet', category: 'retail' },
        { value: 'butchery', label: 'Butchery', category: 'retail' },
        { value: 'boutique', label: 'Boutique', category: 'retail' },
        { value: 'electronics', label: 'Electronics', category: 'retail' },
        { value: 'stationery', label: 'Stationery', category: 'retail' },
        { value: 'general_store', label: 'General Store', category: 'retail' },

        // Services & Personal Care
        { value: 'salon', label: 'Salon', category: 'services' },
        { value: 'barber_shop', label: 'Barber Shop', category: 'services' },
        { value: 'beauty_parlour', label: 'Beauty Parlour', category: 'services' },
        { value: 'tailor', label: 'Tailor', category: 'services' },
        { value: 'shoe_repair', label: 'Shoe Repair', category: 'services' },
        { value: 'photography_studio', label: 'Photography Studio', category: 'services' },
        { value: 'printing_cyber', label: 'Printing/Cyber', category: 'services' },
        { value: 'laundry', label: 'Laundry', category: 'services' },

        // Food & Beverage
        { value: 'cafe', label: 'Café', category: 'food' },
        { value: 'restaurant', label: 'Restaurant', category: 'food' },
        { value: 'fast_food', label: 'Fast-Food Stand', category: 'food' },
        { value: 'mama_mboga', label: 'Mama Mboga', category: 'food' },
        { value: 'milk_atm', label: 'Milk ATM', category: 'food' },
        { value: 'bakery', label: 'Bakery', category: 'food' },

        // Financial & Agency Points
        { value: 'mobile_money', label: 'Mobile Money Agent', category: 'financial' },
        { value: 'bank_agent', label: 'Bank Agent', category: 'financial' },
        { value: 'bill_payment', label: 'Bill Payment', category: 'financial' },
        { value: 'betting_shop', label: 'Betting Shop', category: 'financial' },

        // Transport & Mobility
        { value: 'boda_boda', label: 'Boda Boda', category: 'transport' },
        { value: 'matatu_sacco', label: 'Matatu', category: 'transport' },
        { value: 'fuel_station', label: 'Fuel Station', category: 'transport' },
        { value: 'car_wash', label: 'Car Wash', category: 'transport' },

        // Community & Miscellaneous
        { value: 'church', label: 'Church', category: 'community' },
        { value: 'school_canteen', label: 'School Canteen', category: 'community' },
        { value: 'bar_lounge', label: 'Bar/Lounge', category: 'community' },
        { value: 'pharmacy', label: 'Pharmacy', category: 'community' },
        { value: 'clinic', label: 'Clinic', category: 'community' },

        { value: 'other', label: 'Other (please specify)', category: 'other' },
    ];

    const campaignTypes = [
        { value: 'music', label: 'Music' },
        { value: 'movies', label: 'Movies' },
        { value: 'games', label: 'Games' },
        { value: 'mobile_apps', label: 'Mobile Apps' },
        { value: 'product_launch', label: 'Product Launch' },
        { value: 'product_activation', label: 'Product Activation' },
        { value: 'events', label: 'Events & Promotions' },
        { value: 'education', label: 'Education & Learning' },
    ];

    const musicGenres = [
        'Afrobeat', 'Benga', 'Blues', 'Classical', 'Country', 'Electronic',
        'Folk', 'Funk', 'Gospel', 'Hip Hop', 'Jazz', 'Kwaito', 'Pop', 'R&B',
        'Reggae', 'Rock', 'Soul', 'Traditional', 'Other'
    ];

    const operatingDays = [
        'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'
    ];

    return (
        <>
            <Head title="Become a Digital Content Distributor" />
            <ToastContainer />

            <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 py-8 px-4">
                <div className="max-w-4xl mx-auto">
                    {/* Header */}
                    <div className="text-center mb-8">
                        <h1 className="text-4xl font-bold text-gray-900 mb-4">Become a Digital Content Distributor</h1>
                        <div className="w-24 h-1 bg-blue-600 mx-auto mb-4"></div>
                        <p className="text-lg text-gray-600 max-w-2xl mx-auto">
                            Share content, earn rewards, and grow with Africa's leading digital distribution network
                        </p>
                    </div>

                    {/* Video Section */}
                    <div className="bg-white rounded-lg shadow-md p-6 mb-8">
                        <div className="aspect-video bg-gray-200 rounded-lg flex items-center justify-center">
                            <p className="text-gray-500">Explainer Video Placeholder</p>
                        </div>
                        <p className="text-center text-sm text-gray-600 mt-4">
                            Watch this explainer to learn how you can earn by sharing digital content at your business
                        </p>
                    </div>

                    {/* Flash Messages */}
                    {flash?.success && (
                        <Alert className="mb-6 border-green-200 bg-green-50">
                            <CheckCircle className="h-4 w-4 text-green-600" />
                            <AlertDescription className="text-green-800">
                                {flash.success}
                            </AlertDescription>
                        </Alert>
                    )}

                    {flash?.error && (
                        <Alert className="mb-6 border-red-200 bg-red-50">
                            <AlertDescription className="text-red-800">
                                {flash.error}
                            </AlertDescription>
                        </Alert>
                    )}

                    {/* Required Field Helper */}
                    <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                        <p className="text-sm text-blue-800">
                            All fields marked with <span className="text-red-500 font-bold">*</span> are required
                        </p>
                    </div>

                    <form onSubmit={submit} className="space-y-8">
                        {/* Referral & Identification Section */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <MapPin className="h-5 w-5" />
                                    Referral & Identification
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                {/* Referral Code */}
                                <div>
                                    <Label htmlFor="referral_code">Referral Code (Optional)</Label>
                                    <Input
                                        id="referral_code"
                                        type="text"
                                        value={data.referral_code}
                                        onChange={(e) => setData('referral_code', e.target.value)}
                                        placeholder="Enter DA referral code if applicable"
                                        className="uppercase"
                                    />
                                    <p className="text-xs text-gray-500 mt-1">
                                        Using default referral code from DDS Admin. If you have a specific referral code from an ambassador, you can replace this.
                                    </p>
                                </div>

                                {/* Full Name */}
                                <div>
                                    <Label htmlFor="full_name">
                                        Full Name <span className="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        id="full_name"
                                        type="text"
                                        value={data.full_name}
                                        onChange={(e) => setData('full_name', e.target.value)}
                                        required
                                        placeholder="Enter your full name (must match national ID)"
                                    />
                                    {errors.full_name && (
                                        <p className="mt-1 text-sm text-red-600">{errors.full_name}</p>
                                    )}
                                </div>

                                {/* National ID */}
                                <div>
                                    <Label htmlFor="national_id">
                                        National ID <span className="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        id="national_id"
                                        type="text"
                                        value={data.national_id}
                                        onChange={(e) => setData('national_id', e.target.value)}
                                        required
                                        placeholder="Enter your national ID number"
                                    />
                                    {errors.national_id && (
                                        <p className="mt-1 text-sm text-red-600">{errors.national_id}</p>
                                    )}
                                </div>

                                {/* Date of Birth */}
                                <div>
                                    <Label htmlFor="dob">
                                        Date of Birth <span className="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        id="dob"
                                        type="date"
                                        value={data.dob}
                                        onChange={(e) => setData('dob', e.target.value)}
                                        required
                                        max={new Date(Date.now() - 18 * 365 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]}
                                    />
                                    <p className="text-xs text-gray-500 mt-1">Must be 18 years or older</p>
                                    {errors.dob && (
                                        <p className="mt-1 text-sm text-red-600">{errors.dob}</p>
                                    )}
                                </div>

                                {/* Gender */}
                                <div>
                                    <Label htmlFor="gender">Gender</Label>
                                    <Select value={data.gender} onValueChange={(value) => setData('gender', value)}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select gender (optional)" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="male">Male</SelectItem>
                                            <SelectItem value="female">Female</SelectItem>
                                            <SelectItem value="other">Other</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                {/* Email */}
                                <div>
                                    <Label htmlFor="email">
                                        Email Address <span className="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        value={data.email}
                                        onChange={(e) => setData('email', e.target.value)}
                                        required
                                        placeholder="preferred@email.com"
                                    />
                                    <p className="text-xs text-gray-500 mt-1">Preferred for official correspondence</p>
                                    {errors.email && (
                                        <p className="mt-1 text-sm text-red-600">{errors.email}</p>
                                    )}
                                </div>

                                {/* Geographic Fields */}
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <Label htmlFor="country">Country <span className="text-red-500">*</span></Label>
                                        <Select
                                            value={data.country}
                                            onValueChange={(value) => {
                                                setData('country', value);
                                                setData('county', '');
                                                setData('subcounty', '');
                                                setCounties([]);
                                                setSubcounties([]);
                                                if (value) fetchCounties(parseInt(value));
                                            }}
                                            disabled={countriesLoading}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select country" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {countries.map((country) => (
                                                    <SelectItem key={country.id} value={country.id.toString()}>
                                                        {country.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div>
                                        <Label htmlFor="county">County <span className="text-red-500">*</span></Label>
                                        <Select
                                            value={data.county}
                                            onValueChange={(value) => {
                                                setData('county', value);
                                                setData('subcounty', '');
                                                setSubcounties([]);
                                                if (value) fetchSubcounties(parseInt(value));
                                            }}
                                            disabled={countiesLoading || !data.country}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select county" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {counties.map((county) => (
                                                    <SelectItem key={county.id} value={county.id.toString()}>
                                                        {county.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div>
                                        <Label htmlFor="subcounty">Sub-County <span className="text-red-500">*</span></Label>
                                        <Select
                                            value={data.subcounty}
                                            onValueChange={(value) => setData('subcounty', value)}
                                            disabled={subcountiesLoading || !data.county}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select sub-county" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {subcounties.map((subcounty) => (
                                                    <SelectItem key={subcounty.id} value={subcounty.id.toString()}>
                                                        {subcounty.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>

                                {/* Business Address */}
                                <div>
                                    <Label htmlFor="business_address">
                                        Business Address <span className="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        id="business_address"
                                        type="text"
                                        value={data.business_address}
                                        onChange={(e) => setData('business_address', e.target.value)}
                                        required
                                        placeholder="Physical location for verification"
                                    />
                                    {errors.business_address && (
                                        <p className="mt-1 text-sm text-red-600">{errors.business_address}</p>
                                    )}
                                </div>

                                {/* Phone */}
                                <div>
                                    <Label htmlFor="phone">
                                        Phone Number <span className="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        id="phone"
                                        type="tel"
                                        value={data.phone}
                                        onChange={(e) => setData('phone', e.target.value)}
                                        required
                                        placeholder="e.g., 0712 345678"
                                    />
                                    {errors.phone && (
                                        <p className="mt-1 text-sm text-red-600">{errors.phone}</p>
                                    )}
                                </div>

                                {/* GPS Coordinates */}
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <Label htmlFor="latitude">Latitude</Label>
                                        <Input
                                            id="latitude"
                                            type="text"
                                            value={data.latitude}
                                            onChange={(e) => setData('latitude', e.target.value)}
                                            placeholder="Auto-detected"
                                        />
                                    </div>
                                    <div>
                                        <Label htmlFor="longitude">Longitude</Label>
                                        <Input
                                            id="longitude"
                                            type="text"
                                            value={data.longitude}
                                            onChange={(e) => setData('longitude', e.target.value)}
                                            placeholder="Auto-detected"
                                        />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Business Information Section */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Building className="h-5 w-5" />
                                    Business Information
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                {/* Business Name */}
                                <div>
                                    <Label htmlFor="business_name">Business Name (if any)</Label>
                                    <Input
                                        id="business_name"
                                        type="text"
                                        value={data.business_name}
                                        onChange={(e) => setData('business_name', e.target.value)}
                                        placeholder="Official business name"
                                    />
                                </div>

                                {/* Business Types */}
                                <div>
                                    <Label className="text-base font-medium">
                                        Business Type <span className="text-red-500">*</span>
                                    </Label>
                                    <p className="text-sm text-gray-600 mb-4">Select all that apply to your business</p>

                                    <div className="space-y-4">
                                        {['retail', 'services', 'food', 'financial', 'transport', 'community', 'other'].map((category) => (
                                            <div key={category} className="border rounded-lg p-4">
                                                <h4 className="font-medium text-gray-900 mb-3 capitalize">
                                                    {category === 'retail' && '🏪 Retail & Merchant Outlets'}
                                                    {category === 'services' && '💈 Services & Personal Care'}
                                                    {category === 'food' && '🍴 Food & Beverage'}
                                                    {category === 'financial' && '💰 Financial & Agency Points'}
                                                    {category === 'transport' && '🚖 Transport & Mobility'}
                                                    {category === 'community' && '🏠 Community & Miscellaneous'}
                                                    {category === 'other' && '🔧 Other Business Types'}
                                                </h4>
                                                <div className="grid grid-cols-1 md:grid-cols-2 gap-2">
                                                    {businessTypes
                                                        .filter(type => type.category === category)
                                                        .map((type) => (
                                                            <label key={type.value} className="flex items-center space-x-2">
                                                                <Checkbox
                                                                    checked={data.business_types.includes(type.value)}
                                                                    onCheckedChange={(checked) =>
                                                                        handleBusinessTypeChange(type.value, checked as boolean)
                                                                    }
                                                                />
                                                                <span className="text-sm">{type.label}</span>
                                                            </label>
                                                        ))}
                                                </div>
                                            </div>
                                        ))}
                                    </div>

                                    {data.business_types.includes('other') && (
                                        <div className="mt-4">
                                            <Label htmlFor="other_business_type">
                                                Specify Your Business Type <span className="text-red-500">*</span>
                                            </Label>
                                            <Input
                                                id="other_business_type"
                                                type="text"
                                                value={data.other_business_type}
                                                onChange={(e) => setData('other_business_type', e.target.value)}
                                                placeholder="Describe your business type"
                                                required={data.business_types.includes('other')}
                                            />
                                        </div>
                                    )}
                                </div>
                            </CardContent>
                        </Card>

                        {/* Business Traffic & Hours Section */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Clock className="h-5 w-5" />
                                    Business Traffic & Hours
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                {/* Daily Foot Traffic */}
                                <div>
                                    <Label htmlFor="daily_foot_traffic">
                                        Daily Foot Traffic <span className="text-red-500">*</span>
                                    </Label>
                                    <Select value={data.daily_foot_traffic} onValueChange={(value) => setData('daily_foot_traffic', value)}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select Range" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="less_than_50">Less than 50 people</SelectItem>
                                            <SelectItem value="50_200">50-200 people</SelectItem>
                                            <SelectItem value="200_plus">200+ people</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p className="text-xs text-gray-500 mt-1">Estimated number of customers visiting your business daily</p>
                                </div>

                                {/* Operating Hours */}
                                <div>
                                    <Label>Operating Hours</Label>
                                    <div className="grid grid-cols-2 gap-4">
                                        <div>
                                            <Label htmlFor="operating_hours_start">Opening Time</Label>
                                            <Input
                                                id="operating_hours_start"
                                                type="time"
                                                value={data.operating_hours_start}
                                                onChange={(e) => setData('operating_hours_start', e.target.value)}
                                            />
                                        </div>
                                        <div>
                                            <Label htmlFor="operating_hours_end">Closing Time</Label>
                                            <Input
                                                id="operating_hours_end"
                                                type="time"
                                                value={data.operating_hours_end}
                                                onChange={(e) => setData('operating_hours_end', e.target.value)}
                                            />
                                        </div>
                                    </div>
                                    <p className="text-xs text-gray-500 mt-1">When your business is open to customers</p>
                                </div>

                                {/* Operating Days */}
                                <div>
                                    <Label>Operating Days</Label>
                                    <div className="grid grid-cols-2 md:grid-cols-4 gap-2">
                                        {operatingDays.map((day) => (
                                            <label key={day} className="flex items-center space-x-2">
                                                <Checkbox
                                                    checked={data.operating_days.includes(day)}
                                                    onCheckedChange={(checked) =>
                                                        handleOperatingDayChange(day, checked as boolean)
                                                    }
                                                />
                                                <span className="text-sm capitalize">{day}</span>
                                            </label>
                                        ))}
                                    </div>
                                    <div className="flex gap-2 mt-2">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={() => setData('operating_days', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'])}
                                        >
                                            Weekdays
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={() => setData('operating_days', ['saturday', 'sunday'])}
                                        >
                                            Weekend
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={() => setData('operating_days', operatingDays)}
                                        >
                                            All Week
                                        </Button>
                                    </div>
                                    <p className="text-xs text-gray-500 mt-1">Days when your business operates</p>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Campaign Preferences Section */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Music className="h-5 w-5" />
                                    Campaign Preferences
                                </CardTitle>
                                <CardDescription>Choose the types of campaigns you'd like to participate in</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    {campaignTypes.map((type) => (
                                        <label key={type.value} className="flex items-center space-x-2">
                                            <Checkbox
                                                checked={data.campaign_types.includes(type.value)}
                                                onCheckedChange={(checked) =>
                                                    handleCampaignTypeChange(type.value, checked as boolean)
                                                }
                                            />
                                            <span className="text-sm">{type.label}</span>
                                        </label>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>

                        {/* Music Preferences Section */}
                        {data.campaign_types.includes('music') && (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <Music className="h-5 w-5" />
                                        Music Preferences
                                    </CardTitle>
                                    <CardDescription>Help us match you with relevant music campaigns</CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-6">
                                    <div>
                                        <Label className="text-base font-medium">
                                            Favorite Music Genres <span className="text-red-500">*</span>
                                        </Label>
                                        <div className="grid grid-cols-2 md:grid-cols-3 gap-2 mt-2">
                                            {musicGenres.filter(g => g !== 'Other').map((genre) => (
                                                <label key={genre} className="flex items-center space-x-2">
                                                    <Checkbox
                                                        checked={data.music_genres.includes(genre.toLowerCase())}
                                                        onCheckedChange={(checked) =>
                                                            handleMusicGenreChange(genre.toLowerCase(), checked as boolean)
                                                        }
                                                    />
                                                    <span className="text-sm">{genre}</span>
                                                </label>
                                            ))}
                                            <label className="flex items-center space-x-2">
                                                <Checkbox
                                                    checked={data.music_genres.includes('other')}
                                                    onCheckedChange={(checked) =>
                                                        handleMusicGenreChange('other', checked as boolean)
                                                    }
                                                />
                                                <span className="text-sm">Other</span>
                                            </label>
                                        </div>
                                    </div>

                                    {data.music_genres.includes('other') && (
                                        <div>
                                            <Label htmlFor="other_music_genre">
                                                Specify Other Music Genre <span className="text-red-500">*</span>
                                            </Label>
                                            <Input
                                                id="other_music_genre"
                                                type="text"
                                                value={data.other_music_genre}
                                                onChange={(e) => setData('other_music_genre', e.target.value)}
                                                placeholder="Enter your preferred music genre"
                                                required={data.music_genres.includes('other')}
                                            />
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        )}

                        {/* Content Safety Section */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Shield className="h-5 w-5" />
                                    Content Safety Preference
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="flex items-center justify-between p-4 border rounded-lg">
                                    <div>
                                        <Label htmlFor="safe_for_kids" className="text-base font-medium">
                                            Safe for Kids Content Only
                                        </Label>
                                        <p className="text-sm text-gray-600 mt-1">
                                            Only participate in campaigns with content suitable for children and families
                                        </p>
                                    </div>
                                    <label className="relative inline-flex items-center cursor-pointer">
                                        <input
                                            type="checkbox"
                                            className="sr-only peer"
                                            checked={data.safe_for_kids}
                                            onChange={(e) => setData('safe_for_kids', e.target.checked)}
                                        />
                                        <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                    </label>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Wallet Setup Section */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Wallet className="h-5 w-5" />
                                    Wallet Setup
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                <div>
                                    <Label htmlFor="wallet_type">
                                        Preferred Wallet Type <span className="text-red-500">*</span>
                                    </Label>
                                    <Select value={data.wallet_type} onValueChange={(value) => setData('wallet_type', value)}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select Wallet Type" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="personal">Personal</SelectItem>
                                            <SelectItem value="business">Business</SelectItem>
                                            <SelectItem value="both">Both</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <Label htmlFor="wallet_pin">
                                            Wallet PIN (4-digit) <span className="text-red-500">*</span>
                                        </Label>
                                        <Input
                                            id="wallet_pin"
                                            type="password"
                                            value={data.wallet_pin}
                                            onChange={(e) => setData('wallet_pin', e.target.value)}
                                            pattern="[0-9]{4}"
                                            maxLength={4}
                                            required
                                            placeholder="Enter 4-digit PIN"
                                        />
                                    </div>
                                    <div>
                                        <Label htmlFor="confirm_pin">
                                            Confirm Wallet PIN <span className="text-red-500">*</span>
                                        </Label>
                                        <Input
                                            id="confirm_pin"
                                            type="password"
                                            value={data.confirm_pin}
                                            onChange={(e) => setData('confirm_pin', e.target.value)}
                                            pattern="[0-9]{4}"
                                            maxLength={4}
                                            required
                                            placeholder="Confirm 4-digit PIN"
                                        />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Agreement Section */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <FileText className="h-5 w-5" />
                                    Agreement & Consent
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <label className="flex items-start space-x-3">
                                    <Checkbox
                                        checked={data.terms}
                                        onCheckedChange={(checked) => setData('terms', checked as boolean)}
                                        required
                                    />
                                    <span className="text-sm">
                                        I agree to Daya's{' '}
                                        <a
                                            href="https://www.daya.africa/TnC"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="text-blue-600 hover:underline"
                                        >
                                            Digital Content Distributor Terms & Conditions
                                        </a>
                                        <span className="text-red-500 ml-1">*</span>
                                    </span>
                                </label>
                            </CardContent>
                        </Card>

                        {/* Security Section */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Shield className="h-5 w-5" />
                                    Security Verification
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="flex justify-center">
                                    <div ref={turnstileRef} className="flex justify-center"></div>
                                </div>
                                {!turnstileToken && (
                                    <p className="text-center text-sm text-gray-600 mt-4">
                                        Please complete the security verification to continue
                                    </p>
                                )}
                            </CardContent>
                        </Card>

                        {/* Submit Button */}
                        <div className="flex justify-center">
                            <Button
                                type="submit"
                                size="lg"
                                disabled={processing || !turnstileToken}
                                className="px-8 py-3 text-lg"
                            >
                                {processing ? (
                                    <>
                                        <Loader2 className="mr-2 h-5 w-5 animate-spin" />
                                        Registering...
                                    </>
                                ) : (
                                    'Become a Digital Content Distributor'
                                )}
                            </Button>
                        </div>
                    </form>

                    {/* Footer */}
                    <div className="text-center mt-8 text-sm text-gray-600">
                        <p>Need help? Contact us at <a href="mailto:support@daya.africa" className="text-blue-600 hover:underline">support@daya.africa</a></p>
                    </div>
                </div>
            </div>
        </>
    );
}