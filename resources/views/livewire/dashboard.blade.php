<?php

use function Livewire\Volt\{state, computed, on, mount, layout};
use App\Models\FormSubmission;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

layout('livewire.layouts.app');

state(['stats' => [], 'recent_submissions' => [], 'dateFilter' => 'semua_waktu', 'chartData' => []]);

mount(function() {
    $this->loadStats();
});

$loadStats = function() {
    $user = auth()->user();
    
    // Base query setup
    $query = FormSubmission::query();
    
    if ($user->isUser() || $user->isSkpd()) {
        $query->where('user_id', $user->id);
    }
    
    // Apply Date Filter
    $startDate = match($this->dateFilter) {
        'hari_ini' => Carbon::today(),
        'minggu_ini' => Carbon::now()->startOfWeek(),
        'bulan_ini' => Carbon::now()->startOfMonth(),
        default => null,
    };
    
    if ($startDate) {
        $query->where('created_at', '>=', $startDate);
    }
    
    // Clone query for stats to avoid re-evaluating
    $totalQuery = clone $query;
    $pendingQuery = clone $query;
    $processedQuery = clone $query;
    $rejectedQuery = clone $query;
    $completedQuery = clone $query;
    
    $this->stats = [
        'total_submissions' => $totalQuery->count(),
        'pending_submissions' => $pendingQuery->where('status', 'belum diproses')->count(),
        'processed_submissions' => $processedQuery->where('status', 'diproses')->count(),
        'rejected_submissions' => $rejectedQuery->where('status', 'ditolak')->count(),
        'completed_submissions' => $completedQuery->where('status', 'selesai')->count(),
    ];
    
    if ($user->isSuperadmin()) {
        $userQuery = User::query();
        if ($startDate) $userQuery->where('created_at', '>=', $startDate);
        $this->stats['total_users'] = $userQuery->count();
    } elseif ($user->isAdmin()) {
        $userQuery = User::where('group_id', $user->group_id);
        if ($startDate) $userQuery->where('created_at', '>=', $startDate);
        $this->stats['total_users'] = $userQuery->count();
    }
    
    // Recent Submissions
    $recentQuery = clone $query;
    $this->recent_submissions = $recentQuery->with('user')->orderBy('created_at', 'desc')->take(5)->get();
    
    // Chart Data Setup
    $this->chartData = [
        'donut' => [
            $this->stats['pending_submissions'],
            $this->stats['processed_submissions'],
            $this->stats['rejected_submissions'],
            $this->stats['completed_submissions']
        ],
        'bar' => $this->getBarChartData(clone $query)
    ];
};

$getBarChartData = function($query) {
    $submissions = $query->orderBy('created_at', 'asc')->get();
    
    if ($this->dateFilter === 'semua_waktu' || $this->dateFilter === 'bulan_ini') {
        $grouped = $submissions->groupBy(function($item) {
            return $item->created_at->format('Y-m');
        });
        
        // Take last 6 months
        $grouped = $grouped->take(-6);
        
        $labels = $grouped->keys()->map(function($key) {
            return Carbon::createFromFormat('Y-m', $key)->translatedFormat('M Y');
        })->toArray();
        $data = $grouped->map(fn($group) => $group->count())->values()->toArray();
        
    } else {
        $grouped = $submissions->groupBy(function($item) {
            return $item->created_at->format('Y-m-d');
        });
        
        $labels = $grouped->keys()->map(function($key) {
            return Carbon::createFromFormat('Y-m-d', $key)->translatedFormat('d M');
        })->toArray();
        $data = $grouped->map(fn($group) => $group->count())->values()->toArray();
    }
    
    if (empty($labels)) {
        $labels = ['Tidak ada data'];
        $data = [0];
    }
    
    return [
        'labels' => array_values($labels),
        'data' => array_values($data)
    ];
};

$updatedDateFilter = function() {
    $this->loadStats();
    // Dispatch event to Alpine to update charts
    $this->dispatch('stats-updated', chartData: $this->chartData);
};

// Helper for status badge
$getStatusBadgeColor = function($status) {
    return match($status) {
        'belum diproses' => 'bg-amber-100 text-amber-800 border-amber-200',
        'diproses' => 'bg-blue-100 text-blue-800 border-blue-200',
        'selesai' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
        'ditolak' => 'bg-red-100 text-red-800 border-red-200',
        default => 'bg-gray-100 text-gray-800 border-gray-200',
    };
};

?>

<div>
    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            
            <!-- Header & Date Filter -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <h2 class="text-2xl font-bold tracking-tight text-gray-900">Dashboard</h2>
                
                <div class="w-full sm:w-48 relative z-20">
                    <select wire:model.live="dateFilter" class="block w-full px-4 py-2 text-sm border border-gray-300 rounded-xl bg-white text-gray-800 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary shadow-sm transition-all cursor-pointer">
                        <option value="semua_waktu">Semua Waktu</option>
                        <option value="hari_ini">Hari Ini</option>
                        <option value="minggu_ini">Minggu Ini</option>
                        <option value="bulan_ini">Bulan Ini</option>
                    </select>
                </div>
            </div>

            <!-- Stats Grid -->
            <div>
                <h3 class="text-lg font-semibold text-gray-800 mb-4 px-2">Ringkasan Statistik</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6">
                    
                    <!-- Total -->
                    <a href="{{ route('form-submissions.index') }}" wire:navigate class="block bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:-translate-y-1 hover:shadow-md hover:border-indigo-200 transition-all duration-300 group">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-indigo-50 text-indigo-600 rounded-xl group-hover:bg-indigo-100 transition-colors">
                                <flux:icon name="document-duplicate" variant="outline" class="w-6 h-6" />
                            </div>
                        </div>
                        <p class="text-sm font-medium text-gray-500">Total Pendaftaran</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">{{ $stats['total_submissions'] ?? 0 }}</p>
                    </a>

                    <!-- Pending -->
                    <a href="{{ route('form-submissions.index', ['statusFilter' => 'belum diproses']) }}" wire:navigate class="block bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:-translate-y-1 hover:shadow-md hover:border-amber-200 transition-all duration-300 group">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-amber-50 text-amber-600 rounded-xl group-hover:bg-amber-100 transition-colors">
                                <flux:icon name="clock" variant="outline" class="w-6 h-6" />
                            </div>
                        </div>
                        <p class="text-sm font-medium text-gray-500">Belum Diproses</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">{{ $stats['pending_submissions'] ?? 0 }}</p>
                    </a>

                    <!-- Processed -->
                    <a href="{{ route('form-submissions.index', ['statusFilter' => 'diproses']) }}" wire:navigate class="block bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:-translate-y-1 hover:shadow-md hover:border-blue-200 transition-all duration-300 group">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-blue-50 text-blue-600 rounded-xl group-hover:bg-blue-100 transition-colors">
                                <flux:icon name="arrow-path" variant="outline" class="w-6 h-6" />
                            </div>
                        </div>
                        <p class="text-sm font-medium text-gray-500">Sedang Diproses</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">{{ $stats['processed_submissions'] ?? 0 }}</p>
                    </a>
                    
                    <!-- Completed -->
                    <a href="{{ route('form-submissions.index', ['statusFilter' => 'selesai']) }}" wire:navigate class="block bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:-translate-y-1 hover:shadow-md hover:border-emerald-200 transition-all duration-300 group">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl group-hover:bg-emerald-100 transition-colors">
                                <flux:icon name="check-circle" variant="outline" class="w-6 h-6" />
                            </div>
                        </div>
                        <p class="text-sm font-medium text-gray-500">Selesai</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">{{ $stats['completed_submissions'] ?? 0 }}</p>
                    </a>

                    <!-- Rejected -->
                    <a href="{{ route('form-submissions.index', ['statusFilter' => 'ditolak']) }}" wire:navigate class="block bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:-translate-y-1 hover:shadow-md hover:border-red-200 transition-all duration-300 group">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-red-50 text-red-600 rounded-xl group-hover:bg-red-100 transition-colors">
                                <flux:icon name="x-circle" variant="outline" class="w-6 h-6" />
                            </div>
                        </div>
                        <p class="text-sm font-medium text-gray-500">Ditolak</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">{{ $stats['rejected_submissions'] ?? 0 }}</p>
                    </a>

                    @if(auth()->user()->isSuperadmin() || auth()->user()->isAdmin())
                    <!-- Total Users -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:-translate-y-1 hover:shadow-md transition-all duration-300 group col-span-1 sm:col-span-2 lg:col-span-3 xl:col-span-5">
                        <div class="flex items-center">
                            <div class="p-3 bg-gray-50 text-gray-600 rounded-xl group-hover:bg-gray-100 transition-colors">
                                <flux:icon name="users" variant="outline" class="w-6 h-6" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Total Pengguna Aktif</p>
                                <p class="text-3xl font-bold text-gray-900 mt-1">{{ $stats['total_users'] ?? 0 }}</p>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6" wire:ignore>
                <!-- Bar Chart (Tren Pendaftaran) -->
                <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Tren Pendaftaran</h3>
                    <div x-data="barChart(@js($chartData['bar']))" 
                         @stats-updated.window="updateChart($event.detail.chartData.bar)"
                         class="w-full h-72">
                        <div x-ref="chart"></div>
                    </div>
                </div>

                <!-- Donut Chart (Distribusi Status) -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Distribusi Status</h3>
                    <div x-data="donutChart(@js($chartData['donut']))"
                         @stats-updated.window="updateChart($event.detail.chartData.donut)"
                         class="w-full h-72 flex items-center justify-center">
                        <div x-ref="chart"></div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity List -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                    <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                        <flux:icon name="queue-list" class="w-5 h-5 text-gray-500" />
                        Aktivitas Terbaru
                    </h3>
                    <a href="{{ route('form-submissions.index') }}" class="text-sm text-primary hover:text-primary-dark font-medium transition-colors" wire:navigate>Lihat Semua &rarr;</a>
                </div>
                
                <div class="divide-y divide-gray-100">
                    @forelse($recent_submissions as $submission)
                        <div class="p-6 hover:bg-gray-50 transition-colors flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="flex items-start gap-4">
                                <div class="hidden sm:flex shrink-0 p-3 bg-gray-50 rounded-full text-gray-400">
                                    <flux:icon name="document-text" class="w-5 h-5" />
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900 text-base mb-1">{{ $submission->judul_rphd ?? 'Pendaftaran #' . $submission->id }}</p>
                                    <div class="flex flex-wrap items-center gap-3 text-sm text-gray-500">
                                        <span class="flex items-center gap-1">
                                            <flux:icon name="calendar" class="w-4 h-4" />
                                            {{ $submission->created_at->format('d M Y, H:i') }}
                                        </span>
                                        @if(auth()->user()->isSuperadmin() || auth()->user()->isAdmin())
                                            <span class="flex items-center gap-1">
                                                <flux:icon name="user" class="w-4 h-4" />
                                                {{ $submission->user->name }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-between sm:justify-end gap-4 w-full sm:w-auto">
                                <span class="px-3 py-1 text-xs font-medium rounded-full border {{ $this->getStatusBadgeColor($submission->status) }}">
                                    {{ ucfirst($submission->status) }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="p-12 text-center flex flex-col items-center justify-center">
                            <div class="w-16 h-16 bg-gray-50 text-gray-400 rounded-full flex items-center justify-center mb-4">
                                <flux:icon name="inbox" class="w-8 h-8" />
                            </div>
                            <p class="text-gray-500 font-medium">Belum ada aktivitas pendaftaran.</p>
                            <p class="text-gray-400 text-sm mt-1">Pendaftaran yang baru masuk akan muncul di sini.</p>
                        </div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
    
    <!-- Alpine.js Chart Components & ApexCharts CDN -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        function registerCharts() {
            if (typeof Alpine === 'undefined') return;

            Alpine.data('barChart', (initialData) => ({
                chart: null,
                init() {
                    if (this.chart) {
                        this.chart.destroy();
                    }
                    this.$refs.chart.innerHTML = '';
                    
                    const options = {
                        series: [{
                            name: 'Pendaftaran',
                            data: initialData.data
                        }],
                        chart: {
                            type: 'bar',
                            height: 280,
                            toolbar: { show: false },
                            fontFamily: 'inherit'
                        },
                        plotOptions: {
                            bar: {
                                borderRadius: 4,
                                horizontal: false,
                                columnWidth: '40%',
                            }
                        },
                        colors: ['#4f46e5'], // primary color
                        dataLabels: { enabled: false },
                        xaxis: {
                            categories: initialData.labels,
                            axisBorder: { show: false },
                            axisTicks: { show: false }
                        },
                        yaxis: {
                            labels: {
                                formatter: (val) => { return Math.floor(val) }
                            }
                        },
                        grid: {
                            borderColor: '#f3f4f6',
                            strokeDashArray: 4,
                        }
                    };
                    
                    this.chart = new ApexCharts(this.$refs.chart, options);
                    this.chart.render();
                },
                destroy() {
                    if (this.chart) {
                        this.chart.destroy();
                    }
                },
                updateChart(newData) {
                    this.chart.updateSeries([{ data: newData.data }]);
                    this.chart.updateOptions({ xaxis: { categories: newData.labels } });
                }
            }));

            Alpine.data('donutChart', (initialData) => ({
                chart: null,
                init() {
                    if (this.chart) {
                        this.chart.destroy();
                    }
                    this.$refs.chart.innerHTML = '';
                    
                    const options = {
                        series: initialData,
                        chart: {
                            type: 'donut',
                            height: 280,
                            fontFamily: 'inherit'
                        },
                        labels: ['Belum Diproses', 'Diproses', 'Ditolak', 'Selesai'],
                        colors: ['#f59e0b', '#3b82f6', '#ef4444', '#10b981'], // amber, blue, red, emerald
                        plotOptions: {
                            pie: {
                                donut: {
                                    size: '70%',
                                    labels: {
                                        show: true,
                                        total: {
                                            show: true,
                                            label: 'Total',
                                            formatter: function (w) {
                                                return w.globals.seriesTotals.reduce((a, b) => {
                                                    return a + b
                                                }, 0)
                                            }
                                        }
                                    }
                                }
                            }
                        },
                        dataLabels: { enabled: false },
                        legend: { position: 'bottom' },
                        stroke: { show: false }
                    };
                    
                    this.chart = new ApexCharts(this.$refs.chart, options);
                    this.chart.render();
                },
                destroy() {
                    if (this.chart) {
                        this.chart.destroy();
                    }
                },
                updateChart(newData) {
                    this.chart.updateSeries(newData);
                }
            }));
        }

        if (typeof Alpine !== 'undefined') {
            registerCharts();
        } else {
            document.addEventListener('alpine:init', registerCharts);
        }
    </script>
</div>
