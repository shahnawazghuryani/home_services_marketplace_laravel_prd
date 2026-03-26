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
  category: string;
  provider: {
    id: number;
    name: string;
    phone: string;
    city: string;
    service_area: string;
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
  services?: Array<Record<string, string | number>>;
  bookings?: Array<Record<string, string | number>>;
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
    loadingText: 'One-page Angular app is connecting to Laravel API.',
    currentLocation: 'Current location',
    detecting: 'Detecting your location...',
    currentLocationLabel: 'Current location',
    locationDenied: 'Please allow location or type your area manually.',
    apiError: 'Data could not load. Please check the Laravel API.',
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
    loadingText: 'ون پیج اینگولر ایپ لاراول API سے کنیکٹ ہو رہی ہے۔',
    currentLocation: 'موجودہ لوکیشن',
    detecting: 'لوکیشن معلوم کی جا رہی ہے...',
    currentLocationLabel: 'موجودہ لوکیشن',
    locationDenied: 'براؤزر لوکیشن کی اجازت دیں یا دستی طور پر علاقہ لکھیں۔',
    apiError: 'ڈیٹا لوڈ نہیں ہو سکا۔ براہ کرم Laravel API چیک کریں۔',
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
    loadingText: 'ون پيج اينگولر ايپ لاراول API سان ڳنڍجي رهي آهي۔',
    currentLocation: 'هاڻوڪي جڳهه',
    detecting: 'لوڪيشن معلوم ٿي رهي آهي...',
    currentLocationLabel: 'هاڻوڪي جڳهه',
    locationDenied: 'براؤزر لوڪيشن جي اجازت ڏيو يا هٿ سان علائقو لکو۔',
    apiError: 'ڊيٽا لوڊ نه ٿي سگهيو۔ مهرباني ڪري Laravel API چيڪ ڪريو۔',
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
  readonly locale = signal<LocaleKey>('en');
  readonly currentLocationText = signal(COPY.en['allowLocation']);
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

  loginForm = {
    email: '',
    password: '',
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
    title: '',
    short_description: '',
    description: '',
    price: 0,
    price_type: 'fixed',
    duration_minutes: 60,
    is_active: true,
  };

  serviceFormFile: File | null = null;
  dashboardActionLoading = '';
  dashboardCategoryForm = {
    name: '',
    icon: '',
    description: '',
  };
  dashboardCategoryEdit: Record<number, { name: string; icon: string; description: string }> = {};
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

  homeUrl(fragment = ''): string {
    return this.backendUrl('/' + fragment);
  }

  load(): void {
    const params = new URLSearchParams();

    if (this.search().trim()) params.set('search', this.search().trim());
    if (this.location().trim()) params.set('location', this.location().trim());
    if (this.category().trim()) params.set('category', this.category().trim());

    const query = params.toString();
    const url = '/api/landing' + (query ? `?${query}` : '');

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
    this.currentLocationText.set(this.t('allowLocation'));
    if (this.isServicesPage()) {
      this.navigateWithFilters('/services');
      this.loadServicesPage();
      return;
    }

    this.navigateWithFilters('/');
    this.load();
  }

  formatPhone(phone: string): string {
    return phone.replace(/\D+/g, '');
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
          this.providerBookingStatus = {};
          (response.bookings ?? []).forEach((booking) => {
            const id = Number(booking['id'] ?? 0);
            if (id) {
              this.providerBookingStatus[id] = String(booking['status'] ?? 'accepted');
              this.customerReview[id] = {
                rating: 5,
                comment: '',
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

  submitServiceForm(): void {
    this.authLoading.set(true);
    this.authError.set('');

    const formData = new FormData();
    Object.entries(this.serviceForm).forEach(([key, value]) => {
      formData.append(key, String(value));
    });
    formData.set('is_active', this.serviceForm.is_active ? '1' : '0');

    if (this.serviceFormFile) {
      formData.append('image', this.serviceFormFile);
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

    return stored === 'ur' || stored === 'sd' ? stored : 'en';
  }

  private readPath(): string {
    if (typeof window === 'undefined') {
      return '/';
    }

    return window.location.pathname;
  }

  private hydrateFiltersFromQuery(): void {
    if (typeof window === 'undefined') {
      return;
    }

    const params = new URLSearchParams(window.location.search);
    this.search.set(params.get('search') ?? '');
    this.location.set(params.get('location') ?? '');
    this.category.set(params.get('category') ?? '');
  }

  private detectBackendOrigin(): string {
    if (typeof window === 'undefined') {
      return '';
    }

    if (window.location.port === '4200') {
      return 'http://127.0.0.1:8000';
    }

    return window.location.origin;
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
            title: response.service?.title ?? '',
            short_description: response.service?.short_description ?? '',
            description: response.service?.description ?? '',
            price: response.service?.price ?? 0,
            price_type: response.service?.price_type ?? 'fixed',
            duration_minutes: response.service?.duration_minutes ?? 60,
            is_active: response.service?.is_active ?? true,
          };
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
