<footer class="site-footer"><div class="wrap footer-grid"><div class="footer-identity"><a class="brand" href="{{ \App\Support\ContentUrl::resolve($content['field_39'] ?? '/' ) }}"><img width="640" height="640" class="brand-logo" src="{{ \App\Support\ContentUrl::resolve(($content['field_1'] ?? '')) }}" srcset="{{ \App\Support\ResponsiveImage::srcset(($content['field_1'] ?? '')) }}" sizes="(max-width: 760px) 100vw, 640px" alt="شعار عيادات هلال"><span>{{ ($content['field_2'] ?? '') }}<small>{{ ($content['field_3'] ?? '') }}</small></span></a><p>{{ ($content['field_4'] ?? '') }}<br>{{ ($content['field_5'] ?? '') }}</p><a class="footer-social" href="{{ \App\Support\ContentUrl::resolve(($content['field_6'] ?? '')) }}" target="_blank" rel="noopener">{{ ($content['field_7'] ?? '') }}</a></div><nav class="footer-column" aria-label="روابط الفوتر"><h2>اكتشفي عيادات هلال</h2>@foreach(\App\Services\SiteView::menu('footer') as $link)<a href="{{ $link['url'] }}">{{ $link['label'] }}</a>@endforeach</nav><nav class="footer-column" aria-label="الخدمات في الفوتر"><h2>خدمات العيادة</h2>@foreach($services as $service)<a href="{{ route('services.show',$service->slug) }}">{{ $service->name }}</a>@endforeach</nav><div class="footer-column"><h3>{{ ($content['field_21'] ?? '') }}</h3><a href="{{ \App\Support\ContentUrl::resolve($content['field_51'] ?? '#booking') }}">{{ ($content['field_22'] ?? '') }}</a><a href="{{ \App\Support\ContentUrl::resolve($content['field_52'] ?? '#clinics') }}">{{ ($content['field_23'] ?? '') }}</a><a href="{{ \App\Support\ContentUrl::resolve($content['field_53'] ?? '#faq') }}">{{ ($content['field_24'] ?? '') }}</a>@foreach($branches as $branch)@if(filled($branch->phone))<a class="footer-branch-phone" href="tel:{{ preg_replace('/[^+0-9]/', '', $branch->phone) }}">حجز {{ $branch->name }}: <bdi dir="ltr">{{ $branch->phone }}</bdi></a>@endif @endforeach @if(filled($contact['doctor_phone'] ?? ''))<a class="footer-doctor-phone" href="tel:{{ preg_replace('/[^+0-9]/', '', $contact['doctor_phone']) }}">للتواصل مع الطبيب: <bdi dir="ltr">{{ $contact['doctor_phone'] }}</bdi></a>@endif</div></div><div class="wrap footer-bottom"><span>{{ ($content['field_29'] ?? '') }}<span id="footer-year">{{ ($content['field_30'] ?? '') }}</span>{{ ($contact['footer_copyright'] ?? $content['field_31'] ?? '') }}</span><a href="#page-top" aria-label="{{ filled($content['field_32'] ?? '') ? $content['field_32'] : 'العودة لأعلى الصفحة' }}">{{ ($content['field_32'] ?? '') }}</a></div>@if(!empty($contact['email']))<div class="wrap"><a href="mailto:{{ $contact['email'] }}">{{ $contact['email'] }}</a></div>@endif</footer>
@php
    $chatPhone = preg_replace('/[^0-9]/', '', $whatsappNumber);
    $chatPortrait = $sections->firstWhere('section_key', 'about')?->content['field_1'] ?? 'assets/portrait-office.webp';
@endphp
<aside aria-label="تواصل سريع مع العيادة" class="clinic-chat-widget">
    <a class="floating" id="clinic-chat-toggle" aria-controls="clinic-chat" aria-expanded="false" aria-haspopup="dialog" href="{{ ('https://wa.me/'.preg_replace('/[^0-9]/', '', $whatsappNumber)) }}" target="_blank" rel="noopener" aria-label="{{ trim($content['field_34'] ?? '') }}، التواصل مع العيادة على واتساب"><svg class="whatsapp-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" aria-hidden="true"><path fill="currentColor" d="M16 .8A15.1 15.1 0 0 0 3.1 23.9L.8 31.2l7.5-2.2A15.2 15.2 0 1 0 16 .8zm0 27.4c-2.4 0-4.7-.7-6.7-2l-.5-.3-4.4 1.3 1.4-4.3-.3-.5A12.3 12.3 0 1 1 16 28.2zm6.8-9.2c-.4-.2-2.2-1.1-2.6-1.2-.3-.1-.6-.2-.8.2-.3.4-1 1.2-1.2 1.4-.2.3-.4.3-.8.1-.4-.2-1.6-.6-3-1.9-1.1-1-1.9-2.2-2.1-2.6-.2-.4 0-.6.2-.8l.6-.7.4-.6c.1-.2 0-.5 0-.7-.1-.2-.8-2-1.1-2.7-.3-.7-.6-.6-.8-.6h-.7c-.3 0-.7.1-1 .5-.4.4-1.3 1.3-1.3 3.1s1.3 3.5 1.5 3.7c.2.3 2.7 4.1 6.5 5.7.9.4 1.6.6 2.2.7.9.3 1.7.2 2.3.1.7-.1 2.2-.9 2.5-1.8.3-.9.3-1.7.2-1.8-.1-.2-.3-.3-.7-.5z"/></svg>{{ ($content['field_34'] ?? '') }}<span class="clinic-chat-badge" aria-hidden="true"><svg viewBox="0 0 20 20" width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 3h12a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H8l-5 4V4a1 1 0 0 1 1-1Z"/><path d="M6 7h8M6 10h5"/></svg></span></a>
    <section class="clinic-chat" id="clinic-chat" role="dialog" aria-modal="false" aria-labelledby="clinic-chat-title" hidden>
        <header class="clinic-chat-header">
            <img src="{{ \App\Support\ContentUrl::resolve($chatPortrait) }}" width="52" height="52" alt="د. محمد هلال" loading="lazy">
            <div><h2 id="clinic-chat-title">عيادات د. محمد هلال</h2><p>تواصلي مع فريق العيادة</p></div>
            <button type="button" class="clinic-chat-close" aria-label="إغلاق المحادثة"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg></button>
        </header>
        <div class="clinic-chat-body">
            <div class="clinic-chat-bubble"><p>أهلًا بيكِ، إزاي نقدر نساعدكِ؟ اكتبي رسالتكِ وكمّلي مع فريق العيادة على واتساب.</p></div>
        </div>
        <form class="clinic-chat-form" action="https://wa.me/{{ $chatPhone }}" method="get" target="_blank" rel="noopener noreferrer">
            <label for="clinic-chat-message">رسالتكِ لفريق العيادة</label>
            <textarea id="clinic-chat-message" name="text" rows="2" maxlength="2000" required placeholder="اكتبي رسالتكِ هنا…" dir="auto" aria-describedby="clinic-chat-privacy"></textarea>
            <button class="button" type="submit"><span>إرسال عبر واتساب</span><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4Z M22 2 11 13"/></svg></button>
            <p id="clinic-chat-privacy" aria-live="polite">هتكمّلي الإرسال في واتساب.</p>
        </form>
    </section>
</aside>
<dialog id="video-dialog" class="reel-viewer" aria-labelledby="video-dialog-title">
    <h3 id="video-dialog-title" class="reel-accessible-title">{{ $content['field_36'] ?? 'فيديو د. محمد هلال' }}</h3>
    <button type="button" class="close" aria-label="إغلاق الفيديو"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg></button>
    <div hidden data-video-library>
        @foreach($videoLibrary ?? $videos as $libraryVideo)
            <span data-reel="{{ $libraryVideo->reel ?: $libraryVideo->id }}" data-title="{{ $libraryVideo->title }}" data-src="{{ \App\Support\VideoSource::resolve($libraryVideo->external_video_url ?: ($libraryVideo->getFirstMediaUrl('video-file') ?: $libraryVideo->video_path), $libraryVideo->reel) }}" data-poster="{{ \App\Support\ContentUrl::resolve($libraryVideo->getFirstMediaUrl('video-thumbnail') ?: $libraryVideo->image_path) }}"></span>
        @endforeach
    </div>
    <button type="button" class="reel-nav reel-prev" data-video-step="-1" aria-label="الفيديو السابق"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 5 7 7-7 7"/></svg></button>
    <button type="button" class="reel-nav reel-next" data-video-step="1" aria-label="الفيديو التالي"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m15 5-7 7 7 7"/></svg></button>
    <div id="video-container"></div>
    <div class="reel-author">
        <a class="reel-profile" data-reel-facebook href="https://www.facebook.com/HelalClinic0" target="_blank" rel="noopener"><img src="{{ \App\Support\ContentUrl::resolve($chatPortrait) }}" width="40" height="40" alt=""><span>د. محمد هلال</span></a>
        <span aria-hidden="true">·</span><a class="reel-follow" data-reel-facebook href="https://www.facebook.com/HelalClinic0" target="_blank" rel="noopener" lang="en">Follow</a>
        <span class="reel-audio"><svg viewBox="0 0 24 24" width="15" height="15" fill="currentColor" aria-hidden="true"><path d="M9 3v12.2A4 4 0 1 0 11 19V7l8-2v8.2A4 4 0 1 0 21 17V1z"/></svg>د. محمد هلال · Original audio</span>
    </div>
</dialog>
