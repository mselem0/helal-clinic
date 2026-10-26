import { test } from '@e2e-dev/web';
import { expect } from 'e2e';

test.beforeEach(async ({ app, browser }) => {
  await app.open('/__e2e/reset');
  await browser.route(/^https?:\/\/(?!127\.0\.0\.1|localhost)/, route => route.abort());
});

for (const width of [390, 1440]) {
  test(`ads homepage restores original video and remains clean at ${width}px`, async ({ app, browser }) => {
    await browser.setViewport({width, height: 1000});
    await app.open('/');
    await expect(browser.locator('.feature-preview')).toHaveAttribute('data-title', 'سكر الحمل: طبيعي ولا قيصري؟');
    await expect(browser.locator('.feature-preview')).toHaveAttribute('data-src', /\/reel-1122351437148430\.mp4$/);
    const clean = await browser.evaluate(() => ({
      text: document.documentElement.outerHTML,
      fits: document.documentElement.scrollWidth <= innerWidth + 1,
    }));
    expect(clean.fits).toBe(true);
    expect(/الحقن المجهري|العقم|تأخر (?:الإنجاب|الانجاب)|service-2|article-2|hero-pregnancy-ultrasound/.test(clean.text)).toBe(false);
    await app.screenshot('ads-home-'+width);
    await browser.locator('.feature-preview').click();
    await expect(browser.locator('#video-dialog')).toBeVisible();
    await expect(browser.locator('#video-dialog video')).toHaveAttribute('src', /\/reel-1122351437148430\.mp4$/);
    const playback = await browser.evaluate(async () => {
      const video = document.querySelector('#video-dialog video') as HTMLVideoElement;
      video.muted = true;
      await video.play();
      for (let n=0;n<60 && video.currentTime<0.1;n++) await new Promise(resolve => setTimeout(resolve,100));
      return {time:video.currentTime,duration:video.duration,error:video.error?.code || null};
    });
    expect(playback.time > 0.1).toBe(true);
    expect(playback.duration > 1).toBe(true);
    expect(playback.error).toBe(null);
    await app.screenshot('ads-video-'+width);
    await browser.locator('#video-dialog .close').click();
    await expect(browser.locator('#video-dialog')).not.toBeVisible();
    expect(await browser.evaluate(() => document.activeElement === document.querySelector('.feature-preview'))).toBe(true);
  });
}
