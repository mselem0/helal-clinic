<section class="doctor-profile wrap" aria-labelledby="doctor-profile-title">
    <div class="section-top"><div><span class="eyebrow">تعرّفي على طبيبكِ</span><h2 id="doctor-profile-title">المسيرة العلمية ومجالات التخصص</h2></div></div>
    @if(!empty($profile['headline']))<p class="profile-headline">{{ $profile['headline'] }}</p>@endif
    <div class="profile-grid">
        @if(!empty($profile['qualifications']))
        <article class="profile-card"><span class="profile-mark" aria-hidden="true">01</span><h3>المؤهلات العلمية</h3><ul class="profile-qualifications">
            @foreach($profile['qualifications'] as $qualification)<li><strong>{{ $qualification['title'] }}</strong>@if(!empty($qualification['detail']))<span>{{ $qualification['detail'] }}</span>@endif</li>@endforeach
        </ul></article>
        @endif
        @if(!empty($profile['memberships']))
        <article class="profile-card"><span class="profile-mark" aria-hidden="true">02</span><h3>العضويات المهنية</h3><ul class="profile-memberships">
            @foreach($profile['memberships'] as $membership)<li>{{ $membership['title'] }}</li>@endforeach
        </ul></article>
        @endif
    </div>
    @if(!empty($profile['specialties']))
    <div class="profile-specialties"><h3>مجالات التخصص والرعاية</h3><ul>@foreach($profile['specialties'] as $specialty)<li>{{ $specialty['title'] }}</li>@endforeach</ul><p class="fine">تُحدَّد الفحوصات وخيارات العلاج المناسبة بعد التقييم الطبي لكل حالة.</p></div>
    @endif
</section>
