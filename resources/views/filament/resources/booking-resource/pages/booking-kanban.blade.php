{{-- resources/views/filament/resources/booking-resource/pages/booking-kanban.blade.php --}}
<x-filament-panels::page>
    <div 
        class="flex gap-6 overflow-x-auto pb-4 h-[calc(100vh-250px)]"
        x-data="{
            init() {
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js';
                script.onload = () => {
                    const containers = this.$el.querySelectorAll('.kanban-column-content');
                    containers.forEach(el => {
                        new Sortable(el, {
                            group: 'bookings',
                            animation: 150,
                            ghostClass: 'bg-primary-50',
                            chosenClass: 'border-primary-500',
                            dragClass: 'opacity-50',
                            onEnd: (evt) => {
                                const bookingId = evt.item.getAttribute('data-id');
                                const newStatus = evt.to.getAttribute('data-status');
                                const oldStatus = evt.from.getAttribute('data-status');
                                
                                if (newStatus !== oldStatus) {
                                    @this.updateStatus(bookingId, newStatus);
                                }
                            }
                        });
                    });
                };
                document.head.appendChild(script);
            }
        }"
    >
        @foreach($statuses as $key => $label)
            <div class="flex-shrink-0 w-80 flex flex-col bg-gray-50 rounded-2xl border border-gray-200">
                <div @class([
                    'p-4 border-b rounded-t-2xl flex items-center justify-between sticky top-0 bg-inherit z-10', 
                    'bg-warning-50 border-warning-100 text-warning-700' => $key === 'new', 
                    'bg-success-50 border-success-100 text-success-700' => $key === 'confirmed', 
                    'bg-blue-50 border-blue-100 text-blue-700' => $key === 'in_progress',
                    'bg-gray-100 border-gray-200 text-gray-700' => $key === 'completed', 
                    'bg-danger-50 border-danger-100 text-danger-700' => $key === 'cancelled'
                ])>
                    <h3 class="font-bold text-sm">{{ $label }}</h3>
                    <span class="text-xs bg-white/50 px-2 py-0.5 rounded-full font-bold">{{ $this->getBookings($key)->count() }}</span>
                </div>

                <div 
                    class="kanban-column-content flex-grow overflow-y-auto p-4 space-y-4" 
                    data-status="{{ $key }}"
                    style="min-height: 200px;"
                >
                    @foreach($this->getBookings($key) as $booking)
                        <div 
                            class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 cursor-grab active:cursor-grabbing group transition hover:shadow-md hover:border-primary-200"
                            data-id="{{ $booking->id }}"
                        >
                            <div class="flex justify-between items-start mb-2">
                                <span class="font-bold text-gray-800 text-sm">{{ $booking->patient_name }}</span>
                                <a href="https://wa.me/{{ \App\Helpers\SiteSettingHelper::formatWhatsappNumber($booking->patient_phone) }}" target="_blank" class="text-success-500 opacity-0 group-hover:opacity-100 transition">
                                    <x-heroicon-o-chat-bubble-left-right class="w-4 h-4" />
                                </a>
                            </div>
                            
                            <div class="flex items-center gap-2 mb-3">
                                <div class="text-[10px] text-gray-500 flex items-center gap-1">
                                    <x-heroicon-o-calendar class="w-3 h-3" />
                                    {{ $booking->booking_date->format('Y-m-d') }}
                                </div>
                                <div class="text-[10px] text-gray-500 flex items-center gap-1">
                                    <x-heroicon-o-clock class="w-3 h-3" />
                                    {{ $booking->booking_time }}
                                </div>
                            </div>

                            @if($booking->service)
                                <div class="inline-flex items-center px-2 py-0.5 rounded text-[9px] font-medium bg-primary-50 text-primary-700 border border-primary-100 mb-2">
                                    {{ $booking->service->name }}
                                </div>
                            @endif

                            @if($booking->patient_condition)
                                <p class="text-[10px] text-gray-400 line-clamp-2 italic">
                                    {{ $booking->patient_condition }}
                                </p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <style>
        .kanban-column-content::-webkit-scrollbar {
            width: 4px;
        }
        .kanban-column-content::-webkit-scrollbar-track {
            background: transparent;
        }
        .kanban-column-content::-webkit-scrollbar-thumb {
            background: #e2e8f0;
            border-radius: 10px;
        }
        .kanban-column-content::-webkit-scrollbar-thumb:hover {
            background: #cbd5e1;
        }
    </style>
</x-filament-panels::page>
