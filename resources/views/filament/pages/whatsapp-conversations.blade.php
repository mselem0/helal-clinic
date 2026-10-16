<div class="whatsapp-app-container" dir="rtl" wire:poll.20s="loadContacts">
    <!-- Main App Wrapper -->
    <div class="whatsapp-main">
        
        <!-- Sidebar (Right in RTL) -->
        <aside class="whatsapp-sidebar">
            <!-- Sidebar Header -->
            <header class="sidebar-header">
                <div class="avatar-wrapper">
                    <img src="{{ asset('assets/logo.png') }}" alt="Admin">
                </div>
                <div class="header-actions">
                    <!-- Actions removed to simplify interface -->
                </div>
            </header>

            <!-- Search Area -->
            <div class="search-container">
                <div class="search-box">
                    <button class="search-icon">
                        <svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M15.009 13.805h-.636l-.22-.219a5.184 5.184 0 0 0 1.256-3.386 5.207 5.207 0 1 0-5.207 5.208 5.183 5.183 0 0 0 3.385-1.255l.221.22v.635l4.004 3.999 1.194-1.194-3.997-4.028zm-4.808 0a3.605 3.605 0 1 1 0-7.21 3.605 3.605 0 0 1 0 7.21z"></path></svg>
                    </button>
                    <input type="text" wire:model.live.debounce.300ms="searchQuery" placeholder="البحث في المحادثات...">
                </div>
            </div>

            <!-- Contacts List -->
            <div class="contacts-list custom-scrollbar">
                @forelse($contacts as $contact)
                    <div class="contact-item {{ $selectedPhone === $contact['patient_phone'] ? 'active' : '' }}" 
                         wire:click="selectContact('{{ $contact['patient_phone'] }}')">
                        <div class="contact-avatar">
                            <div class="avatar-default">
                                <svg viewBox="0 0 24 24" width="28" height="28"><path fill="currentColor" d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"></path></svg>
                            </div>
                        </div>
                        <div class="contact-info">
                            <div class="contact-top">
                                <span class="contact-name">{{ $contact['patient_name'] }}</span>
                                <span class="contact-time {{ $contact['unread_count'] > 0 ? 'unread' : '' }}">
                                    @php 
                                        $lastAct = \Carbon\Carbon::parse($contact['last_activity']);
                                        echo $lastAct->isToday() ? $lastAct->format('h:i A') : ($lastAct->isYesterday() ? 'أمس' : $lastAct->format('d/m/y'));
                                    @endphp
                                </span>
                            </div>
                            <div class="contact-bottom">
                                <span class="contact-msg" title="{{ $contact['last_message'] }}">{{ $contact['last_message'] }}</span>
                                @if($contact['unread_count'] > 0)
                                    <span class="unread-badge">{{ $contact['unread_count'] }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="empty-contacts">لا توجد محادثات</div>
                @endforelse
            </div>
        </aside>

        <!-- Main Chat Area -->
        <main class="whatsapp-chat">
            @if($selectedPhone)
                @php $activeContact = collect($contacts)->firstWhere('patient_phone', $selectedPhone); @endphp
                
                <!-- Chat Header -->
                <header class="chat-header">
                    <div class="chat-contact-info">
                        <div class="chat-avatar">
                            <svg viewBox="0 0 24 24" width="24" height="24"><path fill="currentColor" d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"></path></svg>
                        </div>
                        <div class="chat-titles">
                            <h2 class="chat-name">{{ $activeContact['patient_name'] ?? 'مريض' }}</h2>
                            <span class="chat-status">{{ $selectedPhone }}</span>
                        </div>
                    </div>
                    <div class="chat-actions">
                        <x-filament::dropdown placement="bottom-start">
                            <x-slot name="trigger">
                                <button class="icon-btn" title="القوالب"><svg viewBox="0 0 24 24" width="24" height="24"><path fill="currentColor" d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"></path></svg></button>
                            </x-slot>
                            <x-filament::dropdown.list>
                                @foreach($templates as $template)
                                    <x-filament::dropdown.list.item wire:click="useTemplate({{ $template->id }})">
                                        {{ $template->name }}
                                    </x-filament::dropdown.list.item>
                                @endforeach
                            </x-filament::dropdown.list>
                        </x-filament::dropdown>
                    </div>
                </header>

                <!-- Messages Feed -->
                <div class="chat-feed custom-scrollbar" wire:poll.10s="loadThread">
                    @php $lastDate = null; @endphp
                    @foreach($activeThread as $message)
                        @php 
                            $msgDate = \Carbon\Carbon::parse($message['created_at'])->format('Y-m-d');
                            $isOutgoing = in_array($message['source'], ['manual', 'system']);
                        @endphp

                        @if($lastDate !== $msgDate)
                            <div class="date-separator">
                                <span>{{ \Carbon\Carbon::parse($message['created_at'])->isToday() ? 'اليوم' : (\Carbon\Carbon::parse($message['created_at'])->isYesterday() ? 'أمس' : \Carbon\Carbon::parse($message['created_at'])->format('d/m/Y')) }}</span>
                            </div>
                            @php $lastDate = $msgDate; @endphp
                        @endif

                        <div class="msg-row {{ $isOutgoing ? 'outgoing' : 'incoming' }}">
                            <div class="msg-bubble">
                                @if($editingMessageId === $message['id'])
                                    <div class="msg-edit-form">
                                        <textarea wire:model="editingContent" class="msg-edit-input custom-scrollbar"></textarea>
                                        <div class="msg-edit-actions">
                                            <button wire:click="updateMessage" class="edit-save-btn">حفظ</button>
                                            <button wire:click="cancelEditing" class="edit-cancel-btn">إلغاء</button>
                                        </div>
                                    </div>
                                @else
                                    <div class="msg-text">{{ $message['message_content'] }}</div>
                                    
                                    @if($isOutgoing)
                                        <div class="msg-actions-dropdown">
                                            <x-filament::dropdown placement="bottom-end">
                                                <x-slot name="trigger">
                                                    <button class="msg-more-btn">
                                                        <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M12 15.375c-.621 0-1.125.504-1.125 1.125s.504 1.125 1.125 1.125 1.125-.504 1.125-1.125-.504-1.125-1.125-1.125zm0-4.5c-.621 0-1.125.504-1.125 1.125s.504 1.125 1.125 1.125 1.125-.504 1.125-1.125-.504-1.125-1.125-1.125zm0-4.5c-.621 0-1.125.504-1.125 1.125S11.379 8.625 12 8.625s1.125-.504 1.125-1.125S12.621 6.375 12 6.375z"></path></svg>
                                                    </button>
                                                </x-slot>
                                                <x-filament::dropdown.list>
                                                    <x-filament::dropdown.list.item icon="heroicon-o-pencil" wire:click="startEditing({{ $message['id'] }}, '{{ addslashes($message['message_content']) }}')">
                                                        تعديل
                                                    </x-filament::dropdown.list.item>
                                                    <x-filament::dropdown.list.item icon="heroicon-o-trash" color="danger" wire:click="deleteMessage({{ $message['id'] }})">
                                                        حذف
                                                    </x-filament::dropdown.list.item>
                                                </x-filament::dropdown.list>
                                            </x-filament::dropdown>
                                        </div>
                                    @endif

                                    <div class="msg-meta">
                                        <span class="msg-time">{{ \Carbon\Carbon::parse($message['created_at'])->format('h:i A') }}</span>
                                        @if($isOutgoing)
                                            <span class="msg-status {{ $message['status'] === 'contacted' ? '' : 'read' }}">
                                                <svg viewBox="0 0 16 11" width="16" height="11"><path fill="currentColor" d="M15.01 3.316l-.478-.372a.365.365 0 0 0-.51.063L8.666 9.88a.32.32 0 0 1-.484.032l-.358-.325a.32.32 0 0 0-.484.032l-.378.48a.418.418 0 0 0 .036.54l1.32 1.267a.32.32 0 0 0 .484-.032l6.27-7.913a.365.365 0 0 0-.06-.513zm-4.914 0l-.478-.372a.365.365 0 0 0-.51.063L3.75 9.88a.32.32 0 0 1-.484.032L1.86 8.35a.32.32 0 0 0-.484.032l-.378.48a.418.418 0 0 0 .036.54l2.62 2.512a.32.32 0 0 0 .484-.032l6.27-7.913a.365.365 0 0 0-.06-.513z"></path></svg>
                                            </span>
                                        @endif
                                    </div>
                                @endif
                                <div class="bubble-tail">
                                    <svg viewBox="0 0 8 13" width="8" height="13"><path fill="currentColor" d="M1.533 3.568 8 12.193V1H2.812C1.042 1 .474 2.156 1.533 3.568z"></path></svg>
                                </div>
                            </div>
                        </div>
                    @endforeach
                    <div id="chat-anchor"></div>
                </div>

                <!-- Footer / Input -->
                <footer class="chat-footer">
                    <form class="msg-form" wire:submit="sendMessage">
                        <input type="text" wire:model="newMessage" placeholder="اكتب رسالة..." autocomplete="off">
                        <button type="submit" class="send-btn" wire:loading.attr="disabled">
                            <svg viewBox="0 0 24 24" width="24" height="24"><path fill="currentColor" d="M1.101 21.757L23.8 12.028 1.101 2.3l.011 7.912 13.623 1.816-13.623 1.817-.011 7.912z"></path></svg>
                        </button>
                    </form>
                </footer>

            @else
                <!-- Landing Screen -->
                <div class="chat-landing">
                    <div class="landing-content">
                        <div class="landing-icon-container">
                            <div class="landing-circle">
                                <svg viewBox="0 0 24 24" width="80" height="80" color="#00a884"><path fill="currentColor" d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.817 9.817 0 0 0 12.04 2zm.01 1.67c2.2 0 4.26.86 5.82 2.42a8.132 8.132 0 0 1 2.41 5.82c0 4.52-3.67 8.19-8.19 8.19-1.53 0-3.03-.43-4.33-1.24l-.31-.19-3.22.84.86-3.14-.21-.34A8.156 8.156 0 0 1 3.82 12c0-4.52 3.67-8.19 8.19-8.19zM8.47 7.07c-.15 0-.39.06-.6.29-.21.23-.79.77-.79 1.88 0 1.11.81 2.18.92 2.33.11.15 1.58 2.41 3.84 3.38.54.23.96.37 1.29.47.54.17 1.04.15 1.43.09.43-.06 1.33-.54 1.51-1.07.19-.53.19-.98.13-1.07-.06-.09-.23-.15-.48-.27-.25-.12-1.51-.74-1.74-.83-.23-.09-.39-.13-.56.12-.17.25-.65.83-.8 1-.15.17-.29.19-.54.06-.25-.12-1.07-.39-2.03-1.25-.75-.67-1.26-1.5-1.41-1.75-.15-.25-.02-.39.11-.51.11-.11.25-.29.37-.43.12-.15.17-.25.25-.42.08-.17.04-.32-.02-.45-.06-.13-.56-1.35-.77-1.85-.2-.51-.4-.44-.56-.45-.16-.01-.34-.01-.52-.01z"></path></svg>
                            </div>
                        </div>
                        <h1>واتساب د. محمد هلال</h1>
                        <p>تواصل مع مرضاك وتابع حالاتهم الصحية بكل سهولة.<br>استخدم القوالب الذكية للردود السريعة.</p>
                        <div class="landing-footer">
                            <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm3-11h-2V7h-2v2H9v2h2v2h2v-2h2V9z"></path></svg>
                            مشفر تماماً بين الطرفين
                        </div>
                    </div>
                </div>
            @endif
        </main>
    </div>

    @if($isFillingTemplate)
        <div class="confirmation-overlay">
            <div class="confirmation-modal template-modal">
                <div class="conf-header">
                    <h3>تعبئة بيانات القالب</h3>
                    <p>{{ $currentTemplate['name'] }}</p>
                </div>
                
                <div class="template-vars-form custom-scrollbar">
                    @foreach($manualVariables as $var)
                        <div class="var-field">
                            <label>{{ str_replace('_', ' ', $var) }}</label>
                            <input type="text" wire:model.live="templateVariables.{{ $var }}" placeholder="أدخل قيمة {{ $var }}...">
                        </div>
                    @endforeach

                    <div class="template-preview">
                        <label>معاينة الرسالة:</label>
                        <div class="preview-box">
                            @php
                                $preview = $currentTemplate['content'];
                                foreach($templateVariables as $v => $val) {
                                    $preview = str_replace('{' . $v . '}', '<span class="var-highlight">' . ($val ?: '...') . '</span>', $preview);
                                }
                                echo nl2br($preview);
                            @endphp
                        </div>
                    </div>
                </div>

                <div class="confirmation-buttons">
                    <button wire:click="applyTemplate" class="confirm-btn">اعتماد الرسالة</button>
                    <button wire:click="cancelFilling" class="cancel-btn">إلغاء</button>
                </div>
            </div>
        </div>
    @endif

    @if($showConfirmation)
        <div class="confirmation-overlay">
            <div class="confirmation-modal">
                <div class="conf-icon">
                    <svg viewBox="0 0 24 24" width="48" height="48" color="#00a884"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"></path></svg>
                </div>
                <h3>هل قمت بإرسال الرسالة؟</h3>
                <p>سيتم حفظ هذه الرسالة في السجل فقط إذا تم إرسالها بنجاح عبر واتساب.</p>
                <div class="confirmation-buttons">
                    <button wire:click="confirmSend" class="confirm-btn">نعم، تم الإرسال</button>
                    <button wire:click="cancelSend" class="cancel-btn">إلغاء</button>
                </div>
            </div>
        </div>
    @endif

    <style>
        /* Modern WhatsApp Interface - Integration Mode */
        .whatsapp-app-container {
            background-color: transparent;
            font-family: Segoe UI, Helvetica Neue, Helvetica, Lucida Grande, Arial, Ubuntu, Cantarell, Fira Sans, sans-serif;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 0;
            margin: 20px auto;
            height: calc(100vh - 250px);
            min-height: 500px;
            width: 100%;
            overflow: hidden;
        }

        .whatsapp-main {
            width: 100%;
            height: 100%;
            display: flex;
            background-color: #ffffff;
            overflow: hidden;
            box-shadow: 0 6px 18px rgba(0,0,0,0.05);
            border: 1px solid #d1d7db;
            border-radius: 8px;
            max-width: 1600px;
        }

        /* Sidebar */
        .whatsapp-sidebar {
            width: 30%;
            min-width: 320px;
            max-width: 420px;
            display: flex;
            flex-direction: column;
            border-left: 1px solid #d1d7db;
            background: #fff;
            z-index: 10;
        }

        .sidebar-header {
            height: 60px;
            background: #f0f2f5;
            padding: 10px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .avatar-wrapper img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid #d1d7db;
        }

        .header-actions {
            display: flex;
            gap: 12px;
            color: #54656f;
        }

        .icon-btn {
            padding: 8px;
            border-radius: 50%;
            transition: background 0.2s, color 0.2s;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .icon-btn:hover { background: rgba(0,0,0,0.05); color: #111b21; }

        .search-container {
            padding: 7px 12px;
            background: #fff;
        }

        .search-box {
            background: #f0f2f5;
            border-radius: 8px;
            display: flex;
            align-items: center;
            padding: 0 12px;
            height: 35px;
        }

        .search-box input {
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
            flex: 1;
            font-size: 14px;
            padding: 0 10px !important;
            color: #111b21;
        }
        .search-box input::placeholder { color: #667781; }
        .search-icon { color: #54656f; pointer-events: none; }

        /* Contact Items */
        .contacts-list {
            flex: 1;
            overflow-y: auto;
            background: #fff;
        }

        .contact-item {
            display: flex;
            padding: 0 15px;
            height: 72px;
            cursor: pointer;
            transition: background 0.1s;
            align-items: center;
            border-bottom: 1px solid #f0f2f5;
        }
        .contact-item:hover { background: #f5f6f6; }
        .contact-item.active { background: #f0f2f5; }

        .contact-avatar {
            width: 49px;
            height: 49px;
            border-radius: 50%;
            background: #dfe5e7;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            margin-left: 15px;
            flex-shrink: 0;
            overflow: hidden;
        }

        .contact-info {
            flex: 1;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-width: 0;
            padding-left: 5px;
        }

        .contact-top {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
        }
        .contact-name { font-size: 16px; color: #111b21; font-weight: 400; }
        .contact-time { font-size: 12px; color: #667781; }
        .contact-time.unread { color: #00a884; font-weight: 500; }

        .contact-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .contact-msg {
            font-size: 13px;
            color: #667781;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 90%;
        }
        .unread-badge {
            background: #25d366;
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            min-width: 19px;
            height: 19px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 5px;
        }

        /* Main Chat Area */
        .whatsapp-chat {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #efeae2;
            position: relative;
        }

        .chat-header {
            height: 60px;
            background: #f0f2f5;
            padding: 10px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #d1d7db;
            z-index: 100;
        }

        .chat-contact-info {
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
        }
        .chat-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #dfe5e7;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }
        .chat-titles { display: flex; flex-direction: column; }
        .chat-name { font-size: 16px; font-weight: 500; color: #111b21; margin: 0; line-height: 1.2; }
        .chat-status { font-size: 12px; color: #667781; }

        .chat-actions { display: flex; gap: 8px; color: #54656f; }

        /* Messages */
        .chat-feed {
            flex: 1;
            overflow-y: auto;
            padding: 20px 7% 10px;
            background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png');
            background-blend-mode: overlay;
            background-color: rgba(239, 234, 226, 0.96);
            display: flex;
            flex-direction: column;
        }

        .date-separator {
            display: flex;
            justify-content: center;
            margin: 12px 0 20px;
        }
        .date-separator span {
            background: #fff;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 12px;
            color: #54656f;
            box-shadow: 0 1px 1px rgba(0,0,0,0.1);
            text-transform: uppercase;
            font-weight: 500;
        }

        .msg-row { display: flex; width: 100%; margin-bottom: 4px; position: relative; }
        
        /* RTL Logic for Chat: Outgoing (Sent) is Right, Incoming (Received) is Left */
        .msg-row.outgoing { justify-content: flex-start; } /* In RTL, start is Right */
        .msg-row.incoming { justify-content: flex-end; }   /* In RTL, end is Left */

        .msg-bubble {
            max-width: 65%;
            padding: 6px 8px 8px;
            border-radius: 8px;
            box-shadow: 0 1px 1px rgba(0,0,0,0.1);
            position: relative;
            min-width: 60px;
        }

        .outgoing .msg-bubble {
            background: #d9fdd3;
            border-top-right-radius: 0;
            margin-right: 4px;
        }
        .incoming .msg-bubble {
            background: #fff;
            border-top-left-radius: 0;
            margin-left: 4px;
        }

        .msg-text { font-size: 14px; color: #111b21; line-height: 1.4; white-space: pre-wrap; word-break: break-word; margin-bottom: 4px; }

        .msg-meta {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 3px;
            height: 15px;
        }
        .msg-time { font-size: 11px; color: #667781; }
        .msg-status { color: #667781; display: flex; }
        .msg-status.read { color: #53bdeb; }

        .bubble-tail {
            position: absolute;
            top: 0;
            width: 8px;
            height: 13px;
        }
        /* In RTL:
           Outgoing is on Right wall -> tail on Right wall.
           Incoming is on Left wall -> tail on Left wall.
        */
        .outgoing .bubble-tail {
            right: -8px;
            color: #d9fdd3;
        }
        .incoming .bubble-tail {
            left: -8px;
            color: #fff;
            transform: scaleX(-1);
        }

        /* Message Actions */
        .msg-actions-dropdown {
            position: absolute;
            top: 2px;
            left: 2px;
            opacity: 0;
            transition: opacity 0.2s;
        }
        .msg-bubble:hover .msg-actions-dropdown { opacity: 1; }
        .msg-more-btn { color: #54656f; padding: 2px; border-radius: 50%; }
        .msg-more-btn:hover { background: rgba(0,0,0,0.05); }

        /* Editing Form */
        .msg-edit-form { display: flex; flex-direction: column; gap: 8px; min-width: 200px; }
        .msg-edit-input {
            width: 100%;
            background: #fff !important;
            border: 1px solid #d1d7db !important;
            border-radius: 4px !important;
            padding: 8px !important;
            font-size: 14px;
            min-height: 60px;
            resize: vertical;
        }
        .msg-edit-actions { display: flex; justify-content: flex-end; gap: 8px; }
        .edit-save-btn { background: #00a884; color: #fff; padding: 4px 12px; border-radius: 4px; font-size: 12px; }
        .edit-cancel-btn { color: #667781; font-size: 12px; }

        /* Confirmation Modal */
        .confirmation-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(11,20,26,0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            backdrop-blur: 2px;
        }
        .confirmation-modal {
            background: #fff;
            padding: 30px;
            border-radius: 3px;
            width: 100%;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 17px 50px 0 rgba(11,20,26,0.19),0 12px 15px 0 rgba(11,20,26,0.24);
        }
        .conf-icon { margin-bottom: 20px; }
        .confirmation-modal h3 { font-size: 20px; color: #41525d; margin-bottom: 15px; font-weight: 400; }
        .confirmation-modal p { font-size: 14px; color: #667781; margin-bottom: 25px; line-height: 1.5; }
        .confirmation-buttons { display: flex; flex-direction: column; gap: 10px; }
        .confirm-btn { background: #00a884; color: #fff; padding: 12px; border-radius: 3px; font-weight: 500; font-size: 14px; text-transform: uppercase; transition: background 0.2s; }
        .confirm-btn:hover { background: #008f6f; }
        .cancel-btn { color: #00a884; padding: 10px; font-size: 14px; text-transform: uppercase; font-weight: 500; }
        .cancel-btn:hover { background: rgba(0,168,132,0.05); }

        /* Template Modal Specifics */
        .template-modal { max-width: 500px; padding: 20px; }
        .conf-header { border-bottom: 1px solid #f0f2f5; margin-bottom: 20px; padding-bottom: 10px; }
        .conf-header h3 { margin-bottom: 5px; }
        .template-vars-form { max-height: 400px; overflow-y: auto; text-align: right; padding: 0 5px; }
        .var-field { margin-bottom: 15px; }
        .var-field label { display: block; font-size: 12px; color: #667781; margin-bottom: 5px; font-weight: 500; }
        .var-field input { width: 100%; border: 1px solid #d1d7db !important; border-radius: 4px !important; padding: 10px !important; font-size: 14px; }
        
        .template-preview { margin-top: 20px; border-top: 1px solid #f0f2f5; padding-top: 15px; }
        .template-preview label { font-size: 12px; color: #667781; margin-bottom: 8px; display: block; }
        .preview-box { background: #efeae2; padding: 15px; border-radius: 8px; font-size: 14px; line-height: 1.5; color: #111b21; text-align: right; white-space: pre-wrap; }
        .var-highlight { background: #d9fdd3; padding: 0 2px; border-radius: 2px; font-weight: bold; color: #00a884; }

        /* Footer */
        .chat-footer {
            min-height: 62px;
            background: #f0f2f5;
            padding: 5px 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            border-top: 1px solid #d1d7db;
        }

        .msg-form {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .msg-form input {
            flex: 1;
            background: #fff !important;
            border: none !important;
            border-radius: 8px !important;
            padding: 10px 15px !important;
            font-size: 15px;
            color: #111b21;
            box-shadow: none !important;
        }

        .send-btn { color: #54656f; padding: 8px; transform: scaleX(-1); transition: color 0.2s; }
        .send-btn:hover { color: #00a884; }

        /* Landing */
        .chat-landing {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f0f2f5;
            border-bottom: 6px solid #00a884;
            text-align: center;
        }

        .landing-content { max-width: 460px; padding: 0 20px; }
        .landing-circle {
            width: 140px;
            height: 140px;
            background: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 40px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .chat-landing h1 { font-size: 32px; font-weight: 300; color: #41525d; margin-bottom: 18px; }
        .chat-landing p { font-size: 14px; color: #667781; line-height: 1.6; }
        .landing-footer { margin-top: 80px; display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 14px; color: #8696a0; }

        /* Utilities */
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.2); border-radius: 3px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    </style>

    <script>
        document.addEventListener('livewire:initialized', () => {
           const scroller = () => {
               const feed = document.querySelector('.chat-feed');
               if (feed) feed.scrollTop = feed.scrollHeight;
           };
           scroller();
           
           // Handle dynamic updates
           Livewire.on('messageSent', (event) => {
               if (event.url) {
                   window.open(event.url, '_blank');
               }
               setTimeout(scroller, 50);
           });

           Livewire.on('contactSelected', () => setTimeout(scroller, 100));
           
           // Watch for window resize to maintain height
           window.addEventListener('resize', () => {
               const container = document.querySelector('.whatsapp-app-container');
               if (container) {
                   container.style.height = (window.innerHeight - 250) + 'px';
               }
           });
        });
    </script>
</div>
