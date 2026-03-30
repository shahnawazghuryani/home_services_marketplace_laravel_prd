import { CommonModule, DecimalPipe } from '@angular/common';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Component, DestroyRef, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormsModule } from '@angular/forms';

interface Category {
  id: number;
  name: string;
  slug: string;
  description: string;
  services_count: number;
  image_url: string | null;
}

interface ServiceItem {
  id: number;
  title: string;
  slug: string;
  short_description: string;
  price: number;
  duration_minutes: number;
  image_url: string | null;
  category: string | null;
  categories?: string[];
  provider: {
    id: number;
    name: string;
    phone: string;
    city: string;
    service_area: string;
    rating_avg?: number;
    reviews_count?: number;
  };
}

interface ProviderItem {
  id: number;
  name: string;
  phone: string;
  city: string;
  service_area: string;
  bio: string;
  experience_years: number;
  hourly_rate: number;
}

interface UserGuideVideo {
  id: string | number;
  title: string;
  duration: string;
  summary: string;
  audience: string;
  steps: string[];
  voiceover: string[];
  captions: string[];
  videoType?: string | null;
  videoUrl?: string | null;
  videoEmbedUrl?: string | null;
}

interface LandingResponse {
  brand: {
    name: string;
    country: string;
    tagline: string;
    description: string;
  };
  stats: {
    services: number;
    providers: number;
    bookings: number;
    reviews: number;
  };
  categories: Category[];
  services: ServiceItem[];
  providers: ProviderItem[];
  guides?: UserGuideVideo[];
  location_suggestions: string[];
  filters: {
    search?: string;
    location?: string;
    category?: string;
  };
}

interface AuthResponse {
  message: string;
  redirect: string;
}

interface AuthStateResponse {
  logged_in: boolean;
  role: string | null;
}

interface DashboardListItem {
  label?: string;
  total?: number;
  source?: string;
  path?: string;
  device_type?: string;
  visited_at?: string;
}

interface DashboardData {
  role: 'admin' | 'provider' | 'customer';
  user: {
    name: string;
    email: string;
    role: string;
  };
  notifications: Array<{
    title: string;
    message: string;
    type: string;
  }>;
  stats: Record<string, string | number>;
  trafficSummary?: {
    today?: number;
    top_sources?: DashboardListItem[];
    top_pages?: DashboardListItem[];
    latest_visits?: DashboardListItem[];
    your_visits?: number;
    today_site_visits?: number;
    top_source?: string;
  };
  providers?: Array<{
    id: number;
    name: string;
    service_area: string;
    approved: boolean;
    total_views?: number;
    today_views?: number;
    recent_reviews?: Array<{
      rating: number;
      comment: string;
      customer_name: string;
    }>;
    approval_url?: string;
  }>;
  adminCategories?: Array<{
    id: number;
    name: string;
    icon?: string | null;
    description?: string | null;
    update_url: string;
    delete_url: string;
  }>;
  guideVideos?: Array<{
    id: number;
    title: string;
    audience?: string | null;
    summary?: string | null;
    duration?: string | null;
    steps: string[];
    voiceover: string[];
    captions: string[];
    steps_text: string;
    voiceover_text: string;
    captions_text: string;
    video_type: string;
    video_url?: string | null;
    video_path?: string | null;
    sort_order: number;
    is_active: boolean;
    update_url: string;
    delete_url: string;
  }>;
  services?: Array<Record<string, string | number>>;
  bookings?: Array<Record<string, string | number | boolean | { rating: number; comment: string } | null>>;
  categories?: Array<{
    name: string;
    count: number;
    url: string;
  }>;
  profile?: {
    approved: boolean;
    service_area: string;
    availability: string;
    hourly_rate: number;
  };
  actions?: {
    edit_profile_url?: string;
    add_service_url?: string;
  };
  reviews?: Array<{
    rating: number;
    comment: string;
    customer_name: string;
  }>;
  support?: {
    email: string;
    phone: string;
    whatsapp: string;
    hours: string;
  };
  operations?: {
    contact_url?: string;
    provider_onboarding_url?: string;
  };
  logHealth?: {
    has_errors: boolean;
    message?: string | null;
    entries: Array<{ level: string; line: string }>;
  };
}

interface ProviderProfileData {
  user: {
    name: string;
    phone: string;
    city: string;
    address: string;
  };
  profile: {
    bio: string;
    experience_years: number;
    hourly_rate: number;
    service_area: string;
    availability: string;
  };
}

interface ServiceFormData {
  service: null | {
    id: number;
    provider_id?: number;
    category_id: number;
    category_ids?: number[];
    title: string;
    short_description: string;
    description: string;
    price: number;
    price_type: string;
    duration_minutes: number;
    image_path?: string | null;
    is_active: boolean;
  };
  categories: Array<{ id: number; name: string }>;
  providers?: Array<{ id: number; name: string }>;
}

interface AiServiceBuilderResponse {
  title: string;
  short_description: string;
  description: string;
  price: number;
  price_type: string;
  duration_minutes: number;
  suggested_category_ids: number[];
  image_prompt: string;
  generated_image_svg: string;
  image_preview_url: string;
  tags: string[];
  source_prompt: string;
}

interface ServicesIndexResponse {
  services: ServiceItem[];
  categories: Array<{ id: number; name: string; slug: string }>;
  location_suggestions: string[];
  filters: {
    search?: string;
    location?: string;
    category?: string;
  };
  summary: {
    results: number;
    search?: string;
    location?: string;
    category?: string;
  };
}

interface ServiceDetailResponse {
  service: ServiceItem & {
    description: string;
    price_type: string;
  };
  provider_location_label: string;
  provider_map_url: string;
  provider_map_search_url: string;
  related_services: Array<{
    id: number;
    title: string;
    slug: string;
    provider_name: string;
    categories?: string[];
  }>;
  auth: {
    logged_in: boolean;
    role: string | null;
    can_book: boolean;
  };
}

interface ProviderDetailResponse {
  provider: ProviderItem & {
    address: string;
    availability: string;
    approved: boolean;
  };
  location_label: string;
  provider_map_url: string;
  provider_map_search_url: string;
  services: Array<{
    id: number;
    title: string;
    slug: string;
    price: number;
    category: string | null;
    categories?: string[];
  }>;
  reviews: Array<{
    rating: number;
    comment: string;
    customer_name: string;
  }>;
}

interface BookingCreateResponse {
  service: {
    id: number;
    title: string;
    slug: string;
    short_description: string;
    price: number;
    duration_minutes: number;
    provider_name: string;
    provider_phone: string;
  };
  customer: {
    address: string;
  };
  payment_methods: string[];
}

interface AiBookingHelperResponse {
  customer_summary: string;
  missing_fields: string[];
  booking_tip: string;
  risk_flags: string[];
}

interface AiRecommendedProvider {
  id: number;
  name: string;
  phone: string;
  city: string;
  service_area: string;
  hourly_rate: number;
  reason: string;
}

interface AiProviderRecommendationsResponse {
  summary: string;
  providers: AiRecommendedProvider[];
}

type LocaleKey = 'en' | 'ur' | 'sd';
type CopyMap = Record<LocaleKey, Record<string, string>>;

const COPY: CopyMap = {
  en: {
    servicesNav: 'Services',
    providersNav: 'Providers',
    login: 'Login',
    register: 'Register',
    searchTitle: 'Find the right service quickly',
    whatNeed: 'What do you need?',
    whatNeedPlaceholder: 'Plumber, electrician, AC repair',
    location: 'Location',
    locationPlaceholder: 'Karachi, DHA, Clifton',
    category: 'Category',
    allCategories: 'All categories',
    search: 'Search',
    useCurrentLocation: 'Detect now',
    allowLocation: 'Allow browser location or type your area.',
    featuredServices: 'Featured Services',
    topProviders: 'Top Providers',
    topProvidersText: 'Reliable providers ready for home visits and direct contact.',
    clearFilters: 'Clear filters',
    noServices: 'No services found',
    noServicesText: 'Try a different search, category, or location.',
    noProviders: 'No providers found',
    noProvidersText: 'Try a different location or category.',
    mins: 'mins',
    yearsExperience: 'years experience',
    call: 'Call',
    whatsapp: 'WhatsApp',
    viewDetails: 'View details',
    loading: 'Loading GharKaam...',
    loadingText: '',
    currentLocation: 'Current location',
    detecting: 'Detecting your location...',
    currentLocationLabel: 'Current location',
    locationDenied: 'Please allow location or type your area manually.',
    apiError: 'Data could not load. Please check the Laravel API.',
    menu: 'Menu',
    close: 'Close',
    dashboard: 'Dashboard',
    profile: 'Profile',
    noReviewsYet: 'No reviews yet',
    userGuide: 'User Guide',
    guideOnWhatsApp: 'Guide on WhatsApp',
    previewGuide: 'Preview guide',
    loadingServices: 'Loading services...',
    servicesForUsers: 'Services for users',
    provider: 'Provider',
    serviceArea: 'Service area',
    callProvider: 'Call provider',
    aiProviderMatch: 'AI Provider Match',
    finding: 'Finding...',
    getAiRecommendations: 'Get AI Recommendations',
    callNow: 'Call now',
    providerLocation: 'Provider location',
    providerLocationHint: 'See the location on the map and quickly understand the area.',
    openInGoogleMaps: 'Open in Google Maps',
    loadingServiceDetails: 'Loading service details...',
    loadingProviderDetails: 'Loading provider details...',
    loadingBookingForm: 'Loading booking form...',
    thinking: 'Thinking...',
    improveWithAi: 'Improve with AI',
    confirmBooking: 'Confirm booking',
    loadingDashboard: 'Loading dashboard...',
    loadingProfile: 'Loading profile...',
    providerProfile: 'Provider Profile',
    providerProfileSettings: 'Provider profile settings',
    fullName: 'Full name',
    phone: 'Phone',
    city: 'City',
    address: 'Address',
    experienceYears: 'Experience years',
    hourlyRate: 'Hourly rate',
    serviceAreaField: 'Service area',
    availability: 'Availability',
    bio: 'Bio',
    saveProfile: 'Save profile',
    loadingServiceForm: 'Loading service form...',
    adminEdit: 'Admin Edit',
    addService: 'Add service',
    editService: 'Edit service',
    providerPrompt: 'Provider prompt',
    generating: 'Generating...',
    generateServiceDraft: 'Generate service draft',
    saveChanges: 'Save changes',
    loginNow: 'Login Now',
    role: 'Role',
    customer: 'Customer',
    serviceProvider: 'Service Provider',
    createAccount: 'Create account',
    whatsappHelp: 'WhatsApp Help',
    closeGuide: 'Close',
  },
  ur: {
    servicesNav: 'سروسز',
    providersNav: 'پرووائیڈرز',
    login: 'لاگ اِن',
    register: 'رجسٹر',
    searchTitle: 'صحیح سروس جلدی تلاش کریں',
    whatNeed: 'آپ کو کیا چاہیے؟',
    whatNeedPlaceholder: 'پلمبر، الیکٹریشن، اے سی مرمت',
    location: 'لوکیشن',
    locationPlaceholder: 'کراچی، ڈی ایچ اے، کلفٹن',
    category: 'کیٹیگری',
    allCategories: 'تمام کیٹیگریز',
    search: 'سرچ',
    useCurrentLocation: 'ابھی معلوم کریں',
    allowLocation: 'براؤزر لوکیشن کی اجازت دیں یا اپنا علاقہ لکھیں۔',
    featuredServices: 'نمایاں سروسز',
    topProviders: 'بہترین پرووائیڈرز',
    topProvidersText: 'قابلِ اعتماد پرووائیڈرز جو ہوم وزٹ اور رابطے کے لیے تیار ہیں۔',
    clearFilters: 'فلٹر صاف کریں',
    noServices: 'کوئی سروس نہیں ملی',
    noServicesText: 'سرچ، کیٹیگری، یا لوکیشن بدل کر دوبارہ کوشش کریں۔',
    noProviders: 'کوئی پرووائیڈر نہیں ملا',
    noProvidersText: 'لوکیشن یا کیٹیگری بدل کر دوبارہ کوشش کریں۔',
    mins: 'منٹ',
    yearsExperience: 'سال کا تجربہ',
    call: 'کال',
    whatsapp: 'واٹس ایپ',
    viewDetails: 'تفصیل',
    loading: 'گھرکام لوڈ ہو رہا ہے...',
    loadingText: '',
    currentLocation: 'موجودہ لوکیشن',
    detecting: 'لوکیشن معلوم کی جا رہی ہے...',
    currentLocationLabel: 'موجودہ لوکیشن',
    locationDenied: 'براؤزر لوکیشن کی اجازت دیں یا دستی طور پر علاقہ لکھیں۔',
    apiError: 'ڈیٹا لوڈ نہیں ہو سکا۔ براہ کرم Laravel API چیک کریں۔',
    menu: 'مینو',
    close: 'بند کریں',
    dashboard: 'ڈیش بورڈ',
    profile: 'پروفائل',
    noReviewsYet: 'ابھی تک کوئی ریویو نہیں',
    userGuide: 'صارف رہنمائی',
    guideOnWhatsApp: 'واٹس ایپ پر رہنمائی',
    previewGuide: 'رہنمائی دیکھیں',
    loadingServices: 'سروسز لوڈ ہو رہی ہیں...',
    servicesForUsers: 'صارفین کے لیے سروسز',
    provider: 'پرووائیڈر',
    serviceArea: 'سروس ایریا',
    callProvider: 'پرووائیڈر کو کال کریں',
    aiProviderMatch: 'اے آئی پرووائیڈر میچ',
    finding: 'تلاش ہو رہا ہے...',
    getAiRecommendations: 'اے آئی سفارشات لیں',
    callNow: 'ابھی کال کریں',
    providerLocation: 'پرووائیڈر کی لوکیشن',
    providerLocationHint: 'لوکیشن کو نقشے پر دیکھیں اور علاقے کو جلدی سمجھیں۔',
    openInGoogleMaps: 'گوگل میپس میں کھولیں',
    loadingServiceDetails: 'سروس کی تفصیل لوڈ ہو رہی ہے...',
    loadingProviderDetails: 'پرووائیڈر کی تفصیل لوڈ ہو رہی ہے...',
    loadingBookingForm: 'بکنگ فارم لوڈ ہو رہا ہے...',
    thinking: 'سوچا جا رہا ہے...',
    improveWithAi: 'اے آئی سے بہتر بنائیں',
    confirmBooking: 'بکنگ کی تصدیق کریں',
    loadingDashboard: 'ڈیش بورڈ لوڈ ہو رہا ہے...',
    loadingProfile: 'پروفائل لوڈ ہو رہی ہے...',
    providerProfile: 'پرووائیڈر پروفائل',
    providerProfileSettings: 'پرووائیڈر پروفائل سیٹنگز',
    fullName: 'پورا نام',
    phone: 'فون',
    city: 'شہر',
    address: 'پتہ',
    experienceYears: 'تجربے کے سال',
    hourlyRate: 'فی گھنٹہ ریٹ',
    serviceAreaField: 'سروس ایریا',
    availability: 'دستیابی',
    bio: 'تعارف',
    saveProfile: 'پروفائل محفوظ کریں',
    loadingServiceForm: 'سروس فارم لوڈ ہو رہا ہے...',
    adminEdit: 'ایڈمن ترمیم',
    addService: 'سروس شامل کریں',
    editService: 'سروس میں ترمیم',
    providerPrompt: 'پرووائیڈر پرامپٹ',
    generating: 'تیار کیا جا رہا ہے...',
    generateServiceDraft: 'سروس ڈرافٹ بنائیں',
    saveChanges: 'تبدیلیاں محفوظ کریں',
    loginNow: 'ابھی لاگ اِن کریں',
    role: 'کردار',
    customer: 'صارف',
    serviceProvider: 'سروس پرووائیڈر',
    createAccount: 'اکاؤنٹ بنائیں',
    whatsappHelp: 'واٹس ایپ مدد',
    closeGuide: 'بند کریں',
  },
  sd: {
    servicesNav: 'سروسز',
    providersNav: 'پرووائيڊرز',
    login: 'لاگ اِن',
    register: 'رجسٽر',
    searchTitle: 'صحيح سروس جلدي ڳوليو',
    whatNeed: 'توهان کي ڇا گهرجي؟',
    whatNeedPlaceholder: 'پلمبر، اليڪٽريشن، اي سي مرمت',
    location: 'جڳهه',
    locationPlaceholder: 'ڪراچي، ڊي ايڇ اي، ڪلفٽن',
    category: 'ڪيٽيگري',
    allCategories: 'سڀ ڪيٽيگريون',
    search: 'سرچ',
    useCurrentLocation: 'هاڻوڪي جڳهه معلوم ڪريو',
    allowLocation: 'براؤزر لوڪيشن جي اجازت ڏيو يا پنهنجو علائقو لکو۔',
    featuredServices: 'نمايان سروسز',
    topProviders: 'بهترين پرووائيڊرز',
    topProvidersText: 'ڀروسي وارا پرووائيڊرز جيڪي هوم وزٽ ۽ سڌي رابطي لاءِ تيار آهن۔',
    clearFilters: 'فلٽر صاف ڪريو',
    noServices: 'ڪا سروس نه ملي',
    noServicesText: 'سرچ، ڪيٽيگري، يا جڳهه بدلائي ٻيهر ڪوشش ڪريو۔',
    noProviders: 'ڪو پرووائيڊر نه مليو',
    noProvidersText: 'جڳهه يا ڪيٽيگري بدلائي ٻيهر ڪوشش ڪريو۔',
    mins: 'منٽ',
    yearsExperience: 'سالن جو تجربو',
    call: 'ڪال',
    whatsapp: 'واٽس ايپ',
    viewDetails: 'تفصيل',
    loading: 'گهرڪام لوڊ ٿي رهيو آهي...',
    loadingText: '',
    currentLocation: 'هاڻوڪي جڳهه',
    detecting: 'لوڪيشن معلوم ٿي رهي آهي...',
    currentLocationLabel: 'هاڻوڪي جڳهه',
    locationDenied: 'براؤزر لوڪيشن جي اجازت ڏيو يا هٿ سان علائقو لکو۔',
    apiError: 'ڊيٽا لوڊ نه ٿي سگهيو۔ مهرباني ڪري Laravel API چيڪ ڪريو۔',
    menu: 'مينيو',
    close: 'بند ڪريو',
    dashboard: 'ڊيش بورڊ',
    profile: 'پروفائل',
    noReviewsYet: 'اڃا تائين ڪو جائزو ناهي',
    userGuide: 'صارف رهنمائي',
    guideOnWhatsApp: 'واٽس ايپ تي رهنمائي',
    previewGuide: 'رهنمائي ڏسو',
    loadingServices: 'سروسز لوڊ ٿي رهيون آهن...',
    servicesForUsers: 'صارفين لاءِ سروسز',
    provider: 'پرووائيڊر',
    serviceArea: 'سروس ايريا',
    callProvider: 'پرووائيڊر کي ڪال ڪريو',
    aiProviderMatch: 'اي آءِ پرووائيڊر ميچ',
    finding: 'ڳولها هلي رهي آهي...',
    getAiRecommendations: 'اي آءِ سفارشون وٺو',
    callNow: 'هاڻي ڪال ڪريو',
    providerLocation: 'پرووائيڊر جي جڳهه',
    providerLocationHint: 'جڳهه کي نقشي تي ڏسو ۽ علائقي کي جلدي سمجهو۔',
    openInGoogleMaps: 'گوگل ميپس ۾ کوليو',
    loadingServiceDetails: 'سروس جي تفصيل لوڊ ٿي رهي آهي...',
    loadingProviderDetails: 'پرووائيڊر جي تفصيل لوڊ ٿي رهي آهي...',
    loadingBookingForm: 'بڪنگ فارم لوڊ ٿي رهيو آهي...',
    thinking: 'سوچي رهيو آهي...',
    improveWithAi: 'اي آءِ سان بهتر ڪريو',
    confirmBooking: 'بڪنگ جي تصديق ڪريو',
    loadingDashboard: 'ڊيش بورڊ لوڊ ٿي رهيو آهي...',
    loadingProfile: 'پروفائل لوڊ ٿي رهي آهي...',
    providerProfile: 'پرووائيڊر پروفائل',
    providerProfileSettings: 'پرووائيڊر پروفائل سيٽنگون',
    fullName: 'پورو نالو',
    phone: 'فون',
    city: 'شهر',
    address: 'پتو',
    experienceYears: 'تجربي جا سال',
    hourlyRate: 'في ڪلاڪ ريٽ',
    serviceAreaField: 'سروس ايريا',
    availability: 'دستيابي',
    bio: 'تعارف',
    saveProfile: 'پروفائل محفوظ ڪريو',
    loadingServiceForm: 'سروس فارم لوڊ ٿي رهيو آهي...',
    adminEdit: 'ايڊمن ترميم',
    addService: 'سروس شامل ڪريو',
    editService: 'سروس ۾ ترميم',
    providerPrompt: 'پرووائيڊر پرامپٽ',
    generating: 'تيار ٿي رهيو آهي...',
    generateServiceDraft: 'سروس ڊرافٽ ٺاهيو',
    saveChanges: 'تبديليون محفوظ ڪريو',
    loginNow: 'هاڻي لاگ ان ڪريو',
    role: 'ڪردار',
    customer: 'صارف',
    serviceProvider: 'سروس پرووائيڊر',
    createAccount: 'اڪائونٽ ٺاهيو',
    whatsappHelp: 'واٽس ايپ مدد',
    closeGuide: 'بند ڪريو',
  }
};

@Component({
  selector: 'app-root',
  imports: [CommonModule, FormsModule, DecimalPipe],
  templateUrl: './app.html',
  styleUrl: './app.css'
})
export class App {
  private readonly http = inject(HttpClient);
  private readonly destroyRef = inject(DestroyRef);
  private readonly backendOrigin = this.detectBackendOrigin();

  readonly loading = signal(true);
  readonly error = signal('');
  readonly data = signal<LandingResponse | null>(null);
  readonly search = signal('');
  readonly location = signal('');
  readonly category = signal('');
  readonly categoryInput = signal('');
  readonly locationMenuOpen = signal(false);
  readonly categoryMenuOpen = signal(false);
  readonly locale = signal<LocaleKey>('en');
  readonly currentLocationText = signal(COPY.ur['allowLocation']);
  readonly currentPath = signal(this.readPath());
  readonly authLoading = signal(false);
  readonly authError = signal('');
  readonly dashboardLoading = signal(false);
  readonly dashboardError = signal('');
  readonly dashboard = signal<DashboardData | null>(null);
  readonly pageLoading = signal(false);
  readonly pageError = signal('');
  readonly providerProfileData = signal<ProviderProfileData | null>(null);
  readonly serviceFormData = signal<ServiceFormData | null>(null);
  readonly servicesPageData = signal<ServicesIndexResponse | null>(null);
  readonly serviceDetailData = signal<ServiceDetailResponse | null>(null);
  readonly providerDetailData = signal<ProviderDetailResponse | null>(null);
  readonly bookingCreateData = signal<BookingCreateResponse | null>(null);
  readonly aiBookingLoading = signal(false);
  readonly aiBookingResult = signal<AiBookingHelperResponse | null>(null);
  readonly aiProviderLoading = signal(false);
  readonly aiProviderResult = signal<AiProviderRecommendationsResponse | null>(null);
  readonly authState = signal<AuthStateResponse>({ logged_in: false, role: null });
  readonly mobileNavOpen = signal(false);
  readonly activeGuideVideo = signal<UserGuideVideo | null>(null);
  readonly userGuideVideos: UserGuideVideo[] = [
    {
      id: 'customer-search',
      title: 'Service dhoondhna aur provider select karna',
      duration: '01:10',
      summary: 'User ko batayein ke homepage se service, city aur category select karke best provider kaise dekhna hai.',
      audience: 'Naye customer',
      steps: [
        'Homepage khol kar search box mein apni zarurat likhein.',
        'Location aur category select karke results dekhein.',
        'Provider profile aur pricing compare karke service open karein.',
      ],
      voiceover: [
        'Assalamualaikum. GharKaam par aap ghar ke kaam ke liye trusted service asani se dhoondh sakte hain.',
        'Search box mein apni zarurat likhein, jaise plumber, electrician ya cleaning.',
        'Phir location aur category select karke search button dabayein.',
        'Results mein provider ka area, price aur reviews compare karein.',
        'Jo service pasand aaye us ki detail page open karke behtar faisla karein.',
      ],
      captions: [
        'GharKaam par service dhoondhna bohat asaan hai',
        'Apni zarurat likhein aur search karein',
        'Location aur category select karein',
        'Price, area aur reviews compare karein',
        'Best provider choose karke next step par jaayein',
      ],
      videoUrl: null,
    },
    {
      id: 'customer-booking',
      title: 'Account banana aur booking submit karna',
      duration: '01:20',
      summary: 'Signup, login, booking form, aur booking confirmation ka complete flow simple Urdu mein.',
      audience: 'Customer onboarding',
      steps: [
        'Register ya login karein.',
        'Service detail page se booking form open karein.',
        'Date, address aur notes ke saath request submit karein.',
      ],
      voiceover: [
        'Booking karne ke liye sab se pehle apna account bana lein ya login karein.',
        'Register form mein basic details fill karke account create karein.',
        'Login ke baad service detail page par Book button dabayein.',
        'Booking form mein date, address aur notes add karein.',
        'Submit karne ke baad dashboard se booking status dekhte rahein.',
      ],
      captions: [
        'Step 1: Login ya Register karein',
        'Apni basic details fill karein',
        'Book button dabayein',
        'Date, address aur notes enter karein',
        'Dashboard se booking status track karein',
      ],
      videoUrl: null,
    },
    {
      id: 'provider-flow',
      title: 'Provider dashboard aur service create karna',
      duration: '01:30',
      summary: 'Providers ko dashboard, profile update aur AI Service Builder se service create karne ka short guide.',
      audience: 'Service providers',
      steps: [
        'Provider account se login karke dashboard open karein.',
        'Profile aur service area complete karein.',
        'AI prompt ya manual form se nayi service create karein.',
      ],
      voiceover: [
        'Provider login ke baad aap apna poora dashboard use kar sakte hain.',
        'Profile complete karein taake customer ko aap ki information clear nazar aaye.',
        'Add Service page open karke nayi service create karein.',
        'AI Service Builder mein simple prompt likh kar quick draft hasil karein.',
        'Review ke baad service save karein aur customers ke liye live ho jaayein.',
      ],
      captions: [
        'Provider login karein',
        'Dashboard se sab manage karein',
        'Profile complete karein',
        'AI se quick draft banayein',
        'Service save karke live ho jaayein',
      ],
      videoUrl: null,
    },
  ];

  loginForm = {
    email: '',
    password: '',
    redirect_to: '',
  };

  registerForm = {
    name: '',
    email: '',
    phone: '',
    role: 'customer',
    city: '',
    address: '',
    password: '',
    password_confirmation: '',
    service_area: '',
    experience_years: 0,
    hourly_rate: 0,
    availability: '',
    bio: '',
    redirect_to: '',
  };

  providerProfileForm = {
    name: '',
    phone: '',
    city: '',
    address: '',
    bio: '',
    experience_years: 0,
    hourly_rate: 0,
    service_area: '',
    availability: '',
  };

  serviceForm = {
    provider_id: '',
    category_id: '',
    category_ids: [] as string[],
    title: '',
    short_description: '',
    description: '',
    price: 0,
    price_type: '',
    duration_minutes: 0,
    is_active: true,
  };

  serviceFormFile: File | null = null;
  serviceBuilderPrompt = '';
  generatedServiceImageSvg = '';
  generatedServiceImagePreview = '';
  readonly aiServiceBuilderLoading = signal(false);
  readonly aiServiceBuilderResult = signal<AiServiceBuilderResponse | null>(null);
  serviceCategoryExpanded: Record<number, boolean> = {};
  dashboardActionLoading = '';
  dashboardCategoryForm = {
    name: '',
    icon: '',
    description: '',
  };
  dashboardCategoryEdit: Record<number, { name: string; icon: string; description: string }> = {};
  dashboardGuideForm = {
    title: '',
    audience: '',
    summary: '',
    duration: '',
    steps_text: '',
    voiceover_text: '',
    captions_text: '',
    video_type: 'youtube',
    video_url: '',
    sort_order: 0,
    is_active: true,
  };
  dashboardGuideEdit: Record<number, {
    title: string;
    audience: string;
    summary: string;
    duration: string;
    steps_text: string;
    voiceover_text: string;
    captions_text: string;
    video_type: string;
    video_url: string;
    sort_order: number;
    is_active: boolean;
  }> = {};
  dashboardGuideCreateFile: File | null = null;
  dashboardGuideEditFiles: Record<number, File | null> = {};
  providerBookingStatus: Record<number, string> = {};
  customerReview: Record<number, { rating: number; comment: string }> = {};
  bookingForm = {
    scheduled_at: '',
    address: '',
    notes: '',
    payment_method: 'Cash on Service',
  };
  constructor() {
    const storedLocale = this.readStoredLocale();
    this.applyLocale(storedLocale);
    this.hydrateAuthRedirect();
    this.loadAuthState();

    if (this.isHomePage()) {
      this.hydrateFiltersFromQuery();
      this.load();
    } else if (this.isServicesPage()) {
      this.hydrateFiltersFromQuery();
      this.loadServicesPage();
    } else if (this.isServiceDetailPage()) {
      this.loadServiceDetailPage();
    } else if (this.isProviderDetailPage()) {
      this.loadProviderDetailPage();
    } else if (this.isBookingCreatePage()) {
      this.loadBookingCreatePage();
    } else if (this.isDashboardPage()) {
      this.loadDashboard();
    } else if (this.isProviderProfilePage()) {
      this.loadProviderProfilePage();
    } else if (this.isProviderServiceFormPage() || this.isAdminServiceEditPage()) {
      this.loadServiceFormPage();
    } else {
      this.loading.set(false);
    }
  }

  t(key: string): string {
    return COPY[this.locale()][key] ?? COPY.en[key] ?? key;
  }

  setLocale(locale: LocaleKey): void {
    this.applyLocale(locale);
  }

  toggleMobileNav(): void {
    this.mobileNavOpen.update((open) => !open);
  }

  closeMobileNav(): void {
    this.mobileNavOpen.set(false);
  }

  openGuideVideo(guide: UserGuideVideo): void {
    this.activeGuideVideo.set(guide);
  }

  closeGuideVideo(): void {
    this.activeGuideVideo.set(null);
  }

  guideVideosForHome(): UserGuideVideo[] {
    return this.data()?.guides?.length ? this.data()?.guides ?? [] : this.userGuideVideos;
  }

  isHomePage(): boolean {
    return this.currentPath() === '/';
  }

  isLoginPage(): boolean {
    return this.currentPath() === '/login';
  }

  isServicesPage(): boolean {
    return this.currentPath() === '/services';
  }

  isServiceDetailPage(): boolean {
    return /^\/services\/[^/]+$/.test(this.currentPath());
  }

  isProviderDetailPage(): boolean {
    return /^\/providers\/\d+$/.test(this.currentPath());
  }

  isBookingCreatePage(): boolean {
    return /^\/services\/[^/]+\/book$/.test(this.currentPath());
  }

  isRegisterPage(): boolean {
    return this.currentPath() === '/register';
  }

  isDashboardPage(): boolean {
    return this.currentPath() === '/dashboard';
  }

  isProviderProfilePage(): boolean {
    return this.currentPath() === '/provider/profile/edit';
  }

  isProviderServiceCreatePage(): boolean {
    return this.currentPath() === '/provider/services/create';
  }

  isProviderServiceEditPage(): boolean {
    return /^\/provider\/services\/\d+\/edit$/.test(this.currentPath());
  }

  isProviderServiceFormPage(): boolean {
    return this.isProviderServiceCreatePage() || this.isProviderServiceEditPage();
  }

  isAdminServiceEditPage(): boolean {
    return /^\/admin\/services\/\d+\/edit$/.test(this.currentPath());
  }

  isAuthenticatedUiState(): boolean {
    return this.authState().logged_in
      || this.isDashboardPage()
      || this.isProviderProfilePage()
      || this.isProviderServiceFormPage()
      || this.isAdminServiceEditPage()
      || this.isBookingCreatePage()
      || !!this.serviceDetailData()?.auth?.logged_in;
  }

  showProviderNav(): boolean {
    return this.authState().role === 'provider'
      || this.isProviderProfilePage()
      || this.isProviderServiceFormPage()
      || this.dashboard()?.role === 'provider';
  }

  homeUrl(fragment = ''): string {
    if (!fragment) {
      return this.backendUrl('/');
    }

    return `${this.backendUrl('/')}${fragment}`;
  }

  loginUrl(redirectTo?: string): string {
    if (!redirectTo) {
      return this.backendUrl('/login');
    }

    return this.backendUrl(`/login?redirect=${encodeURIComponent(redirectTo)}`);
  }

  supportEmail(): string {
    return this.dashboard()?.support?.email || 'help@gharkaam.pk';
  }

  supportPhone(): string {
    return this.dashboard()?.support?.phone || '03083259933';
  }

  supportPhoneDial(): string {
    return this.formatPhone(this.supportPhone());
  }

  supportWhatsAppNumber(): string {
    return this.dashboard()?.support?.whatsapp || '923083259933';
  }

  supportWhatsAppUrl(): string {
    return `https://wa.me/${this.supportWhatsAppNumber()}`;
  }

  contactUrl(): string {
    return this.dashboard()?.operations?.contact_url || this.backendUrl('/contact');
  }

  privacyUrl(): string {
    return this.backendUrl('/privacy-policy');
  }

  termsUrl(): string {
    return this.backendUrl('/terms-and-conditions');
  }

  providerOnboardingUrl(): string {
    return this.dashboard()?.operations?.provider_onboarding_url || this.backendUrl('/provider-onboarding');
  }

  goToLoginToBook(slug: string): void {
    if (typeof window === 'undefined') {
      return;
    }

    window.location.assign(this.loginUrl(`/services/${slug}/book`));
  }

  goToBooking(slug: string): void {
    if (typeof window === 'undefined') {
      return;
    }

    window.location.assign(this.backendUrl(`/services/${slug}/book`));
  }

  load(): void {
    const params = new URLSearchParams();

    if (this.search().trim()) params.set('search', this.search().trim());
    if (this.location().trim()) params.set('location', this.location().trim());
    if (this.category().trim()) params.set('category', this.category().trim());

    const query = params.toString();
    const url = this.backendUrl('/api/landing') + (query ? `?${query}` : '');

    this.loading.set(true);
    this.error.set('');

    this.http.get<LandingResponse>(url)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.data.set(response);
          this.search.set(response.filters.search ?? this.search());
          this.location.set(response.filters.location ?? this.location());
          this.category.set(response.filters.category ?? this.category());
          this.syncCategoryInput(response.categories);
          this.loading.set(false);
        },
        error: () => {
          this.error.set(this.t('apiError'));
          this.loading.set(false);
        }
      });
  }

  useCurrentLocation(): void {
    if (!navigator.geolocation) {
      this.currentLocationText.set(this.t('locationDenied'));
      return;
    }

    this.currentLocationText.set(this.t('detecting'));

    navigator.geolocation.getCurrentPosition((position) => {
      const coords = `${position.coords.latitude.toFixed(5)}, ${position.coords.longitude.toFixed(5)}`;
      this.location.set(coords);
      this.currentLocationText.set(`${this.t('currentLocationLabel')}: ${coords}`);
    }, () => {
      this.currentLocationText.set(this.t('locationDenied'));
    });
  }

  clearFilters(): void {
    this.search.set('');
    this.location.set('');
    this.category.set('');
    this.categoryInput.set('');
    this.currentLocationText.set(this.t('allowLocation'));
    if (this.isServicesPage()) {
      this.navigateWithFilters('/services');
      this.loadServicesPage();
      return;
    }

    this.navigateWithFilters('/');
    this.load();
  }

  onLocationInputChange(value: string): void {
    this.location.set(value);
    this.locationMenuOpen.set(true);
  }

  onCategoryInputChange(value: string, categories: Array<{ id: number; name: string; slug: string }>): void {
    this.categoryInput.set(value);
    this.categoryMenuOpen.set(true);

    const normalized = value.trim().toLowerCase();
    if (!normalized) {
      this.category.set('');
      return;
    }

    const matched = categories.find((item) =>
      item.name.toLowerCase() === normalized || item.slug.toLowerCase() === normalized
    );

    this.category.set(matched?.slug ?? this.slugify(value));
  }

  openLocationMenu(): void {
    this.locationMenuOpen.set(true);
  }

  openCategoryMenu(): void {
    this.categoryMenuOpen.set(true);
  }

  closeLocationMenu(): void {
    this.locationMenuOpen.set(false);
  }

  closeCategoryMenu(): void {
    this.categoryMenuOpen.set(false);
  }

  deferCloseLocationMenu(): void {
    window.setTimeout(() => this.locationMenuOpen.set(false), 120);
  }

  deferCloseCategoryMenu(): void {
    window.setTimeout(() => this.categoryMenuOpen.set(false), 120);
  }

  filteredLocationSuggestions(suggestions: string[]): string[] {
    const term = this.location().trim().toLowerCase();

    return suggestions
      .filter((suggestion) => !term || suggestion.toLowerCase().includes(term))
      .slice(0, 8);
  }

  filteredCategorySuggestions(categories: Array<{ id: number; name: string; slug: string }>): Array<{ id: number; name: string; slug: string }> {
    const term = this.categoryInput().trim().toLowerCase();

    return categories
      .filter((item) => !term || item.name.toLowerCase().includes(term) || item.slug.toLowerCase().includes(term))
      .slice(0, 8);
  }

  selectLocationSuggestion(suggestion: string): void {
    this.location.set(suggestion);
    this.locationMenuOpen.set(false);
    this.submitServiceSearch();
  }

  selectCategorySuggestion(category: { id: number; name: string; slug: string }): void {
    this.category.set(category.slug);
    this.categoryInput.set(category.name);
    this.categoryMenuOpen.set(false);
    this.submitServiceSearch();
  }

  clearCategorySelection(): void {
    this.category.set('');
    this.categoryInput.set('');
    this.categoryMenuOpen.set(false);
    this.submitServiceSearch();
  }

  applyQuickSearch(search: string, category = '', location = ''): void {
    this.search.set(search);
    this.category.set(category);
    if (location) {
      this.location.set(location);
    }
    this.submitServiceSearch();
  }

  formatPhone(phone: string): string {
    return phone.replace(/\D+/g, '');
  }

  toggleServiceCategory(categoryId: number | string): void {
    const id = String(categoryId);
    const current = [...this.serviceForm.category_ids];
    const exists = current.includes(id);

    this.serviceForm.category_ids = exists
      ? current.filter((value) => value !== id)
      : [...current, id];

    this.serviceForm.category_id = this.serviceForm.category_ids[0] ?? '';
  }

  isServiceCategorySelected(categoryId: number | string): boolean {
    return this.serviceForm.category_ids.includes(String(categoryId));
  }

  serviceCategoriesLabel(service: { category?: string | null; categories?: string[] }): string {
    const categories = service.categories?.filter((value) => value && value.trim()) ?? [];
    if (categories.length > 0) {
      return categories.join(', ');
    }

    return service.category?.trim() || 'Uncategorized';
  }

  serviceCategoryPreview(service: { category?: string | null; categories?: string[] }, limit = 3): string[] {
    const categories = service.categories?.filter((value) => value && value.trim()) ?? [];
    if (categories.length > 0) {
      return categories.slice(0, limit);
    }

    const fallback = service.category?.trim();
    return fallback ? [fallback] : [];
  }

  serviceCategoryMoreCount(service: { category?: string | null; categories?: string[] }, limit = 3): number {
    const categories = service.categories?.filter((value) => value && value.trim()) ?? [];
    return categories.length > limit ? categories.length - limit : 0;
  }

  isServiceCategoriesExpanded(serviceId: number): boolean {
    return !!this.serviceCategoryExpanded[serviceId];
  }

  toggleServiceCategories(serviceId: number): void {
    this.serviceCategoryExpanded[serviceId] = !this.isServiceCategoriesExpanded(serviceId);
  }

  hasPositiveNumber(value: number | string | null | undefined): boolean {
    const numeric = typeof value === 'string' ? Number(value) : value;
    return typeof numeric === 'number' && Number.isFinite(numeric) && numeric > 0;
  }

  hasText(value: string | null | undefined): boolean {
    return typeof value === 'string' && value.trim() !== '';
  }

  ratingValue(value: number | null | undefined): number {
    const numeric = typeof value === 'number' ? value : Number(value ?? 0);
    if (!Number.isFinite(numeric)) {
      return 0;
    }

    return Math.max(0, Math.min(5, numeric));
  }

  reviewsCount(value: number | null | undefined): number {
    const numeric = typeof value === 'number' ? value : Number(value ?? 0);
    if (!Number.isFinite(numeric) || numeric <= 0) {
      return 0;
    }

    return Math.floor(numeric);
  }

  ratingStars(value: number | null | undefined): string {
    const rounded = Math.round(this.ratingValue(value));
    return `${'★'.repeat(rounded)}${'☆'.repeat(5 - rounded)}`;
  }

  reviewStarOptions(): number[] {
    return [1, 2, 3, 4, 5];
  }

  setCustomerReviewRating(bookingId: number, rating: number): void {
    if (!this.customerReview[bookingId]) {
      this.customerReview[bookingId] = {
        rating: 5,
        comment: '',
      };
    }

    this.customerReview[bookingId].rating = rating;
  }

  backendUrl(path: string): string {
    return `${this.backendOrigin}${path}`;
  }

  submitLogin(): void {
    this.authLoading.set(true);
    this.authError.set('');

    this.http.post<AuthResponse>(this.backendUrl('/login'), this.loginForm, {
      headers: this.authHeaders()
    }).pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          window.location.href = response.redirect;
        },
        error: (error) => {
          this.authLoading.set(false);
          this.authError.set(error?.error?.message ?? 'Login failed.');
        }
      });
  }

  submitRegister(): void {
    this.authLoading.set(true);
    this.authError.set('');

    this.http.post<AuthResponse>(this.backendUrl('/register'), this.registerForm, {
      headers: this.authHeaders()
    }).pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          window.location.href = response.redirect;
        },
        error: (error) => {
          this.authLoading.set(false);
          this.authError.set(error?.error?.message ?? 'Registration failed.');
        }
      });
  }

  submitServiceSearch(): void {
    if (this.isServicesPage()) {
      this.navigateWithFilters('/services');
      this.loadServicesPage();
      return;
    }

    this.navigateWithFilters('/');
    this.load();
  }

  onServiceFileChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    this.serviceFormFile = input.files?.[0] ?? null;
    if (this.serviceFormFile) {
      this.generatedServiceImageSvg = '';
      this.generatedServiceImagePreview = '';
    }
  }

  loadDashboard(): void {
    this.loading.set(false);
    this.dashboardLoading.set(true);
    this.dashboardError.set('');

    this.http.get<DashboardData>(this.backendUrl('/dashboard/data'), {
      headers: this.authHeaders(),
    }).pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.dashboard.set(response);
          this.dashboardCategoryEdit = {};
          (response.adminCategories ?? []).forEach((category) => {
            this.dashboardCategoryEdit[category.id] = {
              name: category.name,
              icon: category.icon ?? '',
              description: category.description ?? '',
            };
          });
          this.dashboardGuideEdit = {};
          this.dashboardGuideEditFiles = {};
          (response.guideVideos ?? []).forEach((guide) => {
            this.dashboardGuideEdit[guide.id] = {
              title: guide.title,
              audience: guide.audience ?? '',
              summary: guide.summary ?? '',
              duration: guide.duration ?? '',
              steps_text: guide.steps_text ?? '',
              voiceover_text: guide.voiceover_text ?? '',
              captions_text: guide.captions_text ?? '',
              video_type: guide.video_type ?? 'youtube',
              video_url: guide.video_url ?? '',
              sort_order: guide.sort_order ?? 0,
              is_active: !!guide.is_active,
            };
          });
          this.providerBookingStatus = {};
          (response.bookings ?? []).forEach((booking) => {
            const id = Number(booking['id'] ?? 0);
            if (id) {
              this.providerBookingStatus[id] = String(booking['status'] ?? 'accepted');
              this.customerReview[id] = {
                rating: Number((booking['review'] as { rating?: number } | null)?.rating ?? 5),
                comment: String((booking['review'] as { comment?: string } | null)?.comment ?? ''),
              };
            }
          });
          this.dashboardLoading.set(false);
        },
        error: () => {
          this.dashboardError.set('Dashboard load nahi ho saka.');
          this.dashboardLoading.set(false);
        }
      });
  }

  logout(): void {
    this.http.post<AuthResponse>(this.backendUrl('/logout'), {}, {
      headers: this.authHeaders(),
    }).pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          window.location.href = response.redirect;
        },
        error: () => {
          window.location.href = this.backendUrl('/');
        }
      });
  }

  dashboardActionKey(key: string): boolean {
    return this.dashboardActionLoading === key;
  }

  onGuideCreateFileChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    this.dashboardGuideCreateFile = input.files?.[0] ?? null;
  }

  onGuideEditFileChange(guideId: number, event: Event): void {
    const input = event.target as HTMLInputElement;
    this.dashboardGuideEditFiles[guideId] = input.files?.[0] ?? null;
  }

  createCategory(): void {
    this.dashboardActionLoading = 'create-category';
    this.authError.set('');

    this.http.post(this.backendUrl('/admin/categories'), this.dashboardCategoryForm, {
      headers: this.authHeaders(),
    }).pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => {
          this.dashboardCategoryForm = { name: '', icon: '', description: '' };
          this.dashboardActionLoading = '';
          this.loadDashboard();
        },
        error: (error) => {
          this.dashboardActionLoading = '';
          this.authError.set(error?.error?.message ?? 'Category create failed.');
        }
      });
  }

  createGuide(): void {
    this.dashboardActionLoading = 'create-guide';
    this.authError.set('');

    this.http.post(this.backendUrl('/admin/guides'), this.buildGuideFormData(this.dashboardGuideForm, this.dashboardGuideCreateFile), {
      headers: this.multipartHeaders(),
    }).pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => {
          this.dashboardGuideForm = {
            title: '',
            audience: '',
            summary: '',
            duration: '',
            steps_text: '',
            voiceover_text: '',
            captions_text: '',
            video_type: 'youtube',
            video_url: '',
            sort_order: 0,
            is_active: true,
          };
          this.dashboardGuideCreateFile = null;
          this.dashboardActionLoading = '';
          this.loadDashboard();
        },
        error: (error) => {
          this.dashboardActionLoading = '';
          this.authError.set(error?.error?.message ?? 'Guide create failed.');
        }
      });
  }

  updateGuide(guideId: number, url: string): void {
    this.dashboardActionLoading = `update-guide-${guideId}`;
    this.authError.set('');

    this.http.post(url, this.buildGuideFormData(this.dashboardGuideEdit[guideId], this.dashboardGuideEditFiles[guideId] ?? null), {
      headers: this.multipartHeaders(),
    }).pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => {
          this.dashboardGuideEditFiles[guideId] = null;
          this.dashboardActionLoading = '';
          this.loadDashboard();
        },
        error: (error) => {
          this.dashboardActionLoading = '';
          this.authError.set(error?.error?.message ?? 'Guide update failed.');
        }
      });
  }

  updateCategory(categoryId: number, url: string): void {
    this.dashboardActionLoading = `update-category-${categoryId}`;
    this.authError.set('');

    this.http.put(url, this.dashboardCategoryEdit[categoryId], {
      headers: this.authHeaders(),
    }).pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => {
          this.dashboardActionLoading = '';
          this.loadDashboard();
        },
        error: (error) => {
          this.dashboardActionLoading = '';
          this.authError.set(error?.error?.message ?? 'Category update failed.');
        }
      });
  }

  deleteItem(url: string, key: string): void {
    this.dashboardActionLoading = key;
    this.authError.set('');

    this.http.delete(url, {
      headers: this.authHeaders(),
    }).pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => {
          this.dashboardActionLoading = '';
          this.loadDashboard();
        },
        error: (error) => {
          this.dashboardActionLoading = '';
          this.authError.set(error?.error?.message ?? 'Delete failed.');
        }
      });
  }

  toggleProviderApproval(url: string, providerId: number): void {
    this.dashboardActionLoading = `provider-${providerId}`;
    this.authError.set('');

    this.http.post(url, {}, {
      headers: this.authHeaders(),
    }).pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => {
          this.dashboardActionLoading = '';
          this.loadDashboard();
        },
        error: (error) => {
          this.dashboardActionLoading = '';
          this.authError.set(error?.error?.message ?? 'Provider status update failed.');
        }
      });
  }

  updateBookingStatus(url: string, bookingId: number): void {
    this.dashboardActionLoading = `booking-${bookingId}`;
    this.authError.set('');

    this.http.post(url, {
      status: this.providerBookingStatus[bookingId],
    }, {
      headers: this.authHeaders(),
    }).pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => {
          this.dashboardActionLoading = '';
          this.loadDashboard();
        },
        error: (error) => {
          this.dashboardActionLoading = '';
          this.authError.set(error?.error?.message ?? 'Booking update failed.');
        }
      });
  }

  cancelBooking(url: string, bookingId: number): void {
    this.dashboardActionLoading = `cancel-${bookingId}`;
    this.authError.set('');

    this.http.post(url, {
      status: 'cancelled',
    }, {
      headers: this.authHeaders(),
    }).pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => {
          this.dashboardActionLoading = '';
          this.loadDashboard();
        },
        error: (error) => {
          this.dashboardActionLoading = '';
          this.authError.set(error?.error?.message ?? 'Booking cancel failed.');
        }
      });
  }

  submitReview(url: string, bookingId: number): void {
    this.dashboardActionLoading = `review-${bookingId}`;
    this.authError.set('');

    this.http.post(url, this.customerReview[bookingId], {
      headers: this.authHeaders(),
    }).pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => {
          this.dashboardActionLoading = '';
          this.loadDashboard();
        },
        error: (error) => {
          this.dashboardActionLoading = '';
          this.authError.set(error?.error?.message ?? 'Review submit failed.');
        }
      });
  }

  submitProviderProfile(): void {
    this.authLoading.set(true);
    this.authError.set('');

    this.http.put<AuthResponse>(this.backendUrl('/provider/profile'), this.providerProfileForm, {
      headers: this.authHeaders(),
    }).pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => window.location.href = response.redirect,
        error: (error) => {
          this.authLoading.set(false);
          this.authError.set(error?.error?.message ?? 'Profile update failed.');
        }
      });
  }

  submitBooking(): void {
    this.authLoading.set(true);
    this.authError.set('');

    this.http.post<AuthResponse>(this.backendUrl(this.currentPath()), this.bookingForm, {
      headers: this.authHeaders(),
    }).pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => window.location.href = response.redirect,
        error: (error) => {
          this.authLoading.set(false);
          this.authError.set(error?.error?.message ?? 'Booking request failed.');
        }
      });
  }

  runAiBookingHelper(): void {
    const bookingPage = this.bookingCreateData();

    if (!bookingPage) {
      return;
    }

    this.aiBookingLoading.set(true);
    this.authError.set('');

    this.http.post<{ data: AiBookingHelperResponse }>(this.backendUrl('/api/ai/booking-helper'), {
      service_title: bookingPage.service.title,
      problem: this.bookingForm.notes.trim() || bookingPage.service.short_description,
      location: this.bookingForm.address.trim(),
      preferred_time: this.bookingForm.scheduled_at,
      budget: String(bookingPage.service.price),
    }).pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          const data = response.data;
          this.aiBookingResult.set(data);
          if (data.customer_summary) {
            this.bookingForm.notes = data.customer_summary;
          }
          this.aiBookingLoading.set(false);
        },
        error: (error) => {
          this.aiBookingLoading.set(false);
          this.authError.set(error?.error?.message ?? 'AI booking helper temporarily unavailable hai. Please dobara try karein.');
        }
      });
  }

  runAiProviderRecommendations(): void {
    const detail = this.serviceDetailData();

    if (!detail) {
      return;
    }

    this.aiProviderLoading.set(true);
    this.authError.set('');

    this.http.post<{ data: AiProviderRecommendationsResponse }>(this.backendUrl('/api/ai/provider-recommendations'), {
      problem: detail.service.short_description,
      location: detail.service.provider.city,
      category_slug: this.slugify((detail.service.categories?.[0] ?? detail.service.category ?? '')),
      budget: detail.service.price,
    }).pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.aiProviderResult.set(response.data);
          this.aiProviderLoading.set(false);
        },
        error: (error) => {
          this.aiProviderLoading.set(false);
          this.authError.set(error?.error?.message ?? 'AI recommendations temporarily unavailable hain. Please dobara try karein.');
        }
      });
  }

  runAiServiceBuilder(): void {
    const prompt = this.serviceBuilderPrompt.trim();

    if (!prompt) {
      this.authError.set('Provider prompt likhein, jaise plumber, electrical, AC service.');
      return;
    }

    this.aiServiceBuilderLoading.set(true);
    this.authError.set('');

    this.http.post<{ data: AiServiceBuilderResponse }>(this.backendUrl('/api/ai/service-builder'), {
      prompt,
    }, {
      headers: this.authHeaders(),
    }).pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          const draft = response.data;
          this.aiServiceBuilderResult.set(draft);
          this.serviceForm.title = draft.title;
          this.serviceForm.short_description = draft.short_description;
          this.serviceForm.description = draft.description;
          this.serviceForm.price = draft.price;
          this.serviceForm.price_type = draft.price_type || 'fixed';
          this.serviceForm.duration_minutes = draft.duration_minutes;
          this.serviceForm.category_ids = draft.suggested_category_ids.map((id) => String(id));
          this.serviceForm.category_id = this.serviceForm.category_ids[0] ?? '';
          this.generatedServiceImageSvg = draft.generated_image_svg;
          this.generatedServiceImagePreview = draft.image_preview_url;
          this.serviceFormFile = null;
          this.aiServiceBuilderLoading.set(false);
        },
        error: (error) => {
          this.aiServiceBuilderLoading.set(false);
          this.authError.set(error?.error?.message ?? 'AI service builder temporarily unavailable hai. Please dobara try karein.');
        }
      });
  }

  submitServiceForm(): void {
    this.authLoading.set(true);
    this.authError.set('');

    if (this.serviceForm.category_ids.length === 0 && !this.serviceForm.category_id) {
      this.authLoading.set(false);
      this.authError.set('At least one category select karein.');
      return;
    }

    const formData = new FormData();
    if (this.isAdminServiceEditPage()) {
      formData.append('provider_id', this.serviceForm.provider_id);
    }

    if (this.serviceForm.category_ids.length > 0) {
      this.serviceForm.category_ids.forEach((categoryId) => formData.append('category_ids[]', categoryId));
      formData.append('category_id', this.serviceForm.category_ids[0]);
    } else if (this.serviceForm.category_id) {
      formData.append('category_id', this.serviceForm.category_id);
      formData.append('category_ids[]', this.serviceForm.category_id);
    }

    formData.append('title', this.serviceForm.title);
    formData.append('short_description', this.serviceForm.short_description);
    formData.append('description', this.serviceForm.description);
    formData.append('price', String(this.serviceForm.price));
    formData.append('price_type', this.serviceForm.price_type);
    formData.append('duration_minutes', String(this.serviceForm.duration_minutes));
    formData.set('is_active', this.serviceForm.is_active ? '1' : '0');

    if (this.serviceFormFile) {
      formData.append('image', this.serviceFormFile);
    } else if (this.generatedServiceImageSvg) {
      formData.append('generated_image_svg', this.generatedServiceImageSvg);
    }

    const isEdit = this.isProviderServiceEditPage() || this.isAdminServiceEditPage();
    const method = this.isAdminServiceEditPage() ? 'POST' : (isEdit ? 'POST' : 'POST');
    const url = this.serviceFormSubmitUrl();

    if (isEdit) {
      formData.append('_method', 'PUT');
    }

    this.http.request<AuthResponse>(method, url, {
      body: formData,
      headers: new HttpHeaders({
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-XSRF-TOKEN': decodeURIComponent(this.readCookie('XSRF-TOKEN')),
      }),
    }).pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => window.location.href = response.redirect,
        error: (error) => {
          this.authLoading.set(false);
          this.authError.set(error?.error?.message ?? 'Service save failed.');
        }
      });
  }

  private authHeaders(): HttpHeaders {
    const token = decodeURIComponent(this.readCookie('XSRF-TOKEN'));

    return new HttpHeaders({
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-XSRF-TOKEN': token,
    });
  }

  private multipartHeaders(): HttpHeaders {
    const token = decodeURIComponent(this.readCookie('XSRF-TOKEN'));

    return new HttpHeaders({
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-XSRF-TOKEN': token,
    });
  }

  private buildGuideFormData(form: {
    title: string;
    audience: string;
    summary: string;
    duration: string;
    steps_text: string;
    voiceover_text: string;
    captions_text: string;
    video_type: string;
    video_url: string;
    sort_order: number;
    is_active: boolean;
  }, file: File | null): FormData {
    const formData = new FormData();
    formData.append('title', form.title ?? '');
    formData.append('audience', form.audience ?? '');
    formData.append('summary', form.summary ?? '');
    formData.append('duration', form.duration ?? '');
    formData.append('steps_text', form.steps_text ?? '');
    formData.append('voiceover_text', form.voiceover_text ?? '');
    formData.append('captions_text', form.captions_text ?? '');
    formData.append('video_type', form.video_type ?? 'youtube');
    formData.append('video_url', form.video_url ?? '');
    formData.append('sort_order', String(form.sort_order ?? 0));
    formData.append('is_active', form.is_active ? '1' : '0');
    if (file) {
      formData.append('video_file', file);
    }

    return formData;
  }

  private readCookie(name: string): string {
    if (typeof document === 'undefined') {
      return '';
    }

    const target = document.cookie.split('; ').find((cookie) => cookie.startsWith(`${name}=`));

    return target ? target.split('=').slice(1).join('=') : '';
  }

  private navigateWithFilters(path: string): void {
    if (typeof window === 'undefined') {
      return;
    }

    const params = new URLSearchParams();
    if (this.search().trim()) params.set('search', this.search().trim());
    if (this.location().trim()) params.set('location', this.location().trim());
    if (this.category().trim()) params.set('category', this.category().trim());

    const query = params.toString();
    const nextUrl = `${path}${query ? `?${query}` : ''}`;
    window.history.replaceState({}, '', nextUrl);
    this.currentPath.set(path);
  }

  private applyLocale(locale: LocaleKey): void {
    this.locale.set(locale);
    this.currentLocationText.set(this.t('allowLocation'));
    document.documentElement.lang = locale;
    document.documentElement.dir = locale === 'en' ? 'ltr' : 'rtl';
    document.body.classList.remove('locale-en', 'locale-ur', 'locale-sd');
    document.body.classList.add(`locale-${locale}`);
    localStorage.setItem('gharkaam_locale', locale);
  }

  private readStoredLocale(): LocaleKey {
    const stored = localStorage.getItem('gharkaam_locale');

    return stored === 'en' || stored === 'sd' ? stored : 'ur';
  }

  private readPath(): string {
    if (typeof window === 'undefined') {
      return '/';
    }

    const path = window.location.pathname;
    const basePath = this.detectAppBasePath();

    if (basePath !== '' && path.startsWith(basePath)) {
      const trimmed = path.slice(basePath.length);
      return this.normalizeSpaPath(trimmed);
    }

    return this.normalizeSpaPath(path);
  }

  private hydrateFiltersFromQuery(): void {
    if (typeof window === 'undefined') {
      return;
    }

    const params = new URLSearchParams(window.location.search);
    this.search.set(params.get('search') ?? '');
    this.location.set(params.get('location') ?? '');
    this.category.set(params.get('category') ?? '');
    this.categoryInput.set(params.get('category') ?? '');
  }

  private syncCategoryInput(categories: Array<{ id: number; name: string; slug: string }>): void {
    const selected = this.category().trim();
    if (!selected) {
      this.categoryInput.set('');
      return;
    }

    const matched = categories.find((item) => item.slug === selected);
    this.categoryInput.set(matched?.name ?? selected);
  }

  private hydrateAuthRedirect(): void {
    if (typeof window === 'undefined') {
      return;
    }

    const params = new URLSearchParams(window.location.search);
    const redirect = params.get('redirect') ?? '';

    this.loginForm.redirect_to = redirect;
    this.registerForm.redirect_to = redirect;
  }

  private slugify(value: string): string {
    return value
      .toLowerCase()
      .trim()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '');
  }

  private detectBackendOrigin(): string {
    if (typeof window === 'undefined') {
      return '';
    }

    if (window.location.port === '4200') {
      return `${window.location.protocol}//${window.location.hostname}${this.detectAppBasePath(true)}`;
    }

    return `${window.location.origin}${this.detectAppBasePath()}`;
  }

  private detectAppBasePath(forceLocalProjectPath = false): string {
    if (typeof window === 'undefined') {
      return '';
    }

    if (forceLocalProjectPath) {
      return '/home_services_marketplace_laravel_prd/public';
    }

    const path = window.location.pathname;
    const publicIndex = path.indexOf('/public');

    if (publicIndex !== -1) {
      return path.slice(0, publicIndex + '/public'.length);
    }

    return '';
  }

  private normalizeSpaPath(path: string): string {
    if (!path || path === '/') {
      return '/';
    }

    if (path === '/spa' || path === '/spa/') {
      return '/';
    }

    if (path.startsWith('/spa/')) {
      return path.slice('/spa'.length);
    }

    return path;
  }

  private loadAuthState(): void {
    this.http.get<AuthStateResponse>(this.backendUrl('/auth/state'), {
      headers: this.authHeaders(),
    }).pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => this.authState.set(response),
        error: () => this.authState.set({ logged_in: false, role: null }),
      });
  }

  private loadProviderProfilePage(): void {
    this.loading.set(false);
    this.pageLoading.set(true);
    this.pageError.set('');

    this.http.get<ProviderProfileData>(this.backendUrl('/provider/profile/data'), {
      headers: this.authHeaders(),
    }).pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.providerProfileData.set(response);
          this.providerProfileForm = {
            name: response.user.name,
            phone: response.user.phone,
            city: response.user.city,
            address: response.user.address,
            bio: response.profile.bio,
            experience_years: response.profile.experience_years,
            hourly_rate: response.profile.hourly_rate,
            service_area: response.profile.service_area,
            availability: response.profile.availability,
          };
          this.pageLoading.set(false);
        },
        error: () => {
          this.pageError.set('Profile page load nahi ho saka.');
          this.pageLoading.set(false);
        }
      });
  }

  private loadServiceFormPage(): void {
    this.loading.set(false);
    this.pageLoading.set(true);
    this.pageError.set('');

    this.http.get<ServiceFormData>(this.serviceFormDataUrl(), {
      headers: this.authHeaders(),
    }).pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.serviceFormData.set(response);
          this.serviceForm = {
            provider_id: String(response.service?.provider_id ?? ''),
            category_id: String(response.service?.category_id ?? response.categories[0]?.id ?? ''),
            category_ids: response.service?.category_ids?.length
              ? response.service.category_ids.map((id) => String(id))
              : response.service?.category_id
              ? [String(response.service.category_id)]
              : (response.categories[0] ? [String(response.categories[0].id)] : []),
            title: response.service?.title ?? '',
            short_description: response.service?.short_description ?? '',
            description: response.service?.description ?? '',
            price: response.service?.price ?? 0,
            price_type: response.service?.price_type ?? '',
            duration_minutes: response.service?.duration_minutes ?? 0,
            is_active: response.service?.is_active ?? true,
          };
          this.serviceBuilderPrompt = '';
          this.generatedServiceImageSvg = '';
          this.generatedServiceImagePreview = '';
          this.aiServiceBuilderResult.set(null);
          this.pageLoading.set(false);
        },
        error: () => {
          this.pageError.set('Service form load nahi ho saka.');
          this.pageLoading.set(false);
        }
      });
  }

  private loadServicesPage(): void {
    const params = new URLSearchParams();

    if (this.search().trim()) params.set('search', this.search().trim());
    if (this.location().trim()) params.set('location', this.location().trim());
    if (this.category().trim()) params.set('category', this.category().trim());

    const query = params.toString();

    this.loading.set(false);
    this.pageLoading.set(true);
    this.pageError.set('');

    this.http.get<ServicesIndexResponse>(this.backendUrl('/services/data') + (query ? `?${query}` : ''))
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.servicesPageData.set(response);
          this.syncCategoryInput(response.categories);
          this.pageLoading.set(false);
        },
        error: () => {
          this.pageError.set('Services load nahi ho sakin.');
          this.pageLoading.set(false);
        }
      });
  }

  private loadServiceDetailPage(): void {
    this.loading.set(false);
    this.pageLoading.set(true);
    this.pageError.set('');

    this.http.get<ServiceDetailResponse>(this.backendUrl(`${this.currentPath()}/data`))
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.serviceDetailData.set(response);
          this.pageLoading.set(false);
        },
        error: () => {
          this.pageError.set('Service details load nahi ho sakin.');
          this.pageLoading.set(false);
        }
      });
  }

  private loadProviderDetailPage(): void {
    this.loading.set(false);
    this.pageLoading.set(true);
    this.pageError.set('');

    this.http.get<ProviderDetailResponse>(this.backendUrl(`${this.currentPath()}/data`))
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.providerDetailData.set(response);
          this.pageLoading.set(false);
        },
        error: () => {
          this.pageError.set('Provider details load nahi ho sakin.');
          this.pageLoading.set(false);
        }
      });
  }

  private loadBookingCreatePage(): void {
    this.loading.set(false);
    this.pageLoading.set(true);
    this.pageError.set('');

    this.http.get<BookingCreateResponse>(this.backendUrl(`${this.currentPath()}/data`), {
      headers: this.authHeaders(),
    }).pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.bookingCreateData.set(response);
          this.bookingForm = {
            scheduled_at: '',
            address: response.customer.address ?? '',
            notes: '',
            payment_method: response.payment_methods[0] ?? 'Cash on Service',
          };
          this.pageLoading.set(false);
        },
        error: () => {
          this.pageError.set('Booking page load nahi ho saka.');
          this.pageLoading.set(false);
        }
      });
  }

  private serviceFormDataUrl(): string {
    const path = this.currentPath();

    if (this.isProviderServiceCreatePage()) {
      return this.backendUrl('/provider/services/create/data');
    }

    if (this.isProviderServiceEditPage()) {
      return this.backendUrl(`${path}/data`);
    }

    return this.backendUrl(`${path}/data`);
  }

  private serviceFormSubmitUrl(): string {
    const path = this.currentPath();

    if (this.isProviderServiceCreatePage()) {
      return this.backendUrl('/provider/services');
    }

    if (this.isProviderServiceEditPage()) {
      return this.backendUrl(path.replace('/edit', ''));
    }

    return this.backendUrl(path.replace('/edit', ''));
  }
}

