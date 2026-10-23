(function () {
  'use strict';

  const WIDTH = 1080;
  const PAD = 90;
  const CONTENT_WIDTH = WIDTH - PAD * 2;
  const FONT = '"IBM Plex Sans Arabic", sans-serif';
  const COLORS = {
    ink: '#155d62',
    deep: '#075e62',
    body: '#45666a',
    muted: '#6e8584',
    mint: '#eef7f3',
    mintDeep: '#d8ece4',
    white: '#ffffff',
    accent: '#d98d52',
    line: '#dcebe5'
  };

  function text(value) {
    return typeof value === 'string' ? value.trim() : '';
  }

  function setFont(ctx, size, weight) {
    ctx.font = `${weight} ${size}px ${FONT}`;
  }

  function splitLongWord(ctx, word, maxWidth) {
    const pieces = [];
    let piece = '';
    for (const char of Array.from(word)) {
      const candidate = piece + char;
      if (piece && ctx.measureText(candidate).width > maxWidth) {
        pieces.push(piece);
        piece = char;
      } else {
        piece = candidate;
      }
    }
    if (piece) pieces.push(piece);
    return pieces;
  }

  function wrapText(ctx, value, maxWidth) {
    const paragraphs = value.split(/\r?\n/);
    const lines = [];
    paragraphs.forEach((paragraph, paragraphIndex) => {
      const words = paragraph.trim().split(/\s+/).filter(Boolean);
      let line = '';
      words.forEach((word) => {
        const chunks = ctx.measureText(word).width > maxWidth
          ? splitLongWord(ctx, word, maxWidth)
          : [word];
        chunks.forEach((chunk) => {
          const candidate = line ? `${line} ${chunk}` : chunk;
          if (line && ctx.measureText(candidate).width > maxWidth) {
            lines.push(line);
            line = chunk;
          } else {
            line = candidate;
          }
        });
      });
      if (line) lines.push(line);
      if (!line && paragraphIndex < paragraphs.length - 1) lines.push('');
    });
    return lines.length ? lines : [''];
  }

  function roundedRect(ctx, x, y, width, height, radius) {
    ctx.beginPath();
    ctx.roundRect(x, y, width, height, radius);
  }

  function drawLines(ctx, lines, x, y, lineHeight) {
    lines.forEach((line, index) => ctx.fillText(line, x, y + index * lineHeight));
  }

  async function saveHelalJourneyCard({ tag, name, questions } = {}) {
    if (document.fonts && document.fonts.ready) await document.fonts.ready;

    const safeTag = text(tag);
    const safeName = text(name);
    const safeQuestions = Array.isArray(questions)
      ? questions.map(text).filter(Boolean)
      : [];

    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    if (!ctx) throw new Error('Canvas is unavailable in this browser.');

    ctx.direction = 'rtl';
    ctx.textAlign = 'right';
    ctx.textBaseline = 'alphabetic';

    const questionFontSize = 30;
    const questionLineHeight = 48;
    const questionTextWidth = CONTENT_WIDTH - 200;
    setFont(ctx, questionFontSize, 400);
    const rows = safeQuestions.map((question, index) => ({
      number: String(index + 1),
      lines: wrapText(ctx, question, questionTextWidth)
    }));

    const titleY = 194;
    const nameY = safeName ? 270 : 0;
    const tagY = safeTag ? (safeName ? 344 : 270) : (safeName ? 344 : 270);
    const sectionY = Math.max(tagY + 84, 354);
    const rowTop = sectionY + 54;
    const rowPadding = 27;
    const rowGap = 18;
    const rowHeights = rows.map((row) => Math.max(92, row.lines.length * questionLineHeight + rowPadding * 2));
    const rowsBottom = rows.length
      ? rowTop + rowHeights.reduce((sum, height) => sum + height, 0) + rowGap * (rows.length - 1)
      : rowTop + 20;
    const footerTop = rowsBottom + 72;
    const height = Math.max(920, footerTop + 220);
    canvas.width = WIDTH;
    canvas.height = height;

    // Canvas dimensions reset drawing state.
    ctx.direction = 'rtl';
    ctx.textAlign = 'right';
    ctx.textBaseline = 'alphabetic';

    const bg = ctx.createLinearGradient(0, 0, WIDTH, height);
    bg.addColorStop(0, '#f7fbf8');
    bg.addColorStop(1, COLORS.mint);
    ctx.fillStyle = bg;
    ctx.fillRect(0, 0, WIDTH, height);

    // Soft branded corner shapes keep the card calm and leave the text clear.
    ctx.fillStyle = 'rgba(216, 236, 228, 0.62)';
    ctx.beginPath();
    ctx.arc(WIDTH + 30, 48, 220, 0, Math.PI * 2);
    ctx.fill();
    ctx.fillStyle = 'rgba(217, 141, 82, 0.14)';
    ctx.beginPath();
    ctx.arc(PAD - 48, height - 40, 145, 0, Math.PI * 2);
    ctx.fill();

    roundedRect(ctx, PAD, 62, CONTENT_WIDTH, height - 124, 34);
    ctx.fillStyle = COLORS.white;
    ctx.fill();
    ctx.strokeStyle = '#e4efea';
    ctx.lineWidth = 2;
    ctx.stroke();

    ctx.fillStyle = COLORS.accent;
    roundedRect(ctx, WIDTH - PAD - 12, 112, 12, 72, 6);
    ctx.fill();

    ctx.fillStyle = COLORS.deep;
    setFont(ctx, 46, 600);
    ctx.fillText('رحلتكِ مع د. محمد هلال', WIDTH - PAD - 40, titleY);

    if (safeName) {
      ctx.fillStyle = COLORS.body;
      setFont(ctx, 31, 500);
      ctx.fillText(`يا ${safeName}، كل رحلة تبدأ بخطوة.`, WIDTH - PAD - 40, nameY, CONTENT_WIDTH - 80);
    }

    if (safeTag) {
      ctx.fillStyle = COLORS.accent;
      setFont(ctx, 32, 600);
      ctx.fillText(safeTag, WIDTH - PAD - 40, tagY, CONTENT_WIDTH - 80);
    }

    ctx.fillStyle = COLORS.ink;
    setFont(ctx, 27, 600);
    ctx.fillText('أسئلتكِ قبل الزيارة', WIDTH - PAD - 40, sectionY);

    let y = rowTop;
    rows.forEach((row, index) => {
      const rowHeight = rowHeights[index];
      const right = WIDTH - PAD - 40;
      roundedRect(ctx, PAD + 40, y, CONTENT_WIDTH - 80, rowHeight, 18);
      ctx.fillStyle = index % 2 === 0 ? '#f5faf7' : '#fbfdfb';
      ctx.fill();
      ctx.strokeStyle = COLORS.line;
      ctx.lineWidth = 1.5;
      ctx.stroke();

      ctx.fillStyle = COLORS.mintDeep;
      ctx.beginPath();
      ctx.arc(right - 25, y + rowHeight / 2, 25, 0, Math.PI * 2);
      ctx.fill();
      ctx.fillStyle = COLORS.deep;
      ctx.textAlign = 'center';
      ctx.direction = 'ltr';
      setFont(ctx, 21, 600);
      ctx.fillText(row.number, right - 25, y + rowHeight / 2 + 7);
      ctx.textAlign = 'right';
      ctx.direction = 'rtl';

      ctx.fillStyle = COLORS.body;
      setFont(ctx, questionFontSize, 400);
      const textY = y + (rowHeight - row.lines.length * questionLineHeight) / 2 + 35;
      drawLines(ctx, row.lines, right - 68, textY, questionLineHeight);
      y += rowHeight + rowGap;
    });

    const footerY = rowsBottom + 72;
    ctx.strokeStyle = COLORS.line;
    ctx.lineWidth = 2;
    ctx.beginPath();
    ctx.moveTo(PAD + 40, footerY - 34);
    ctx.lineTo(WIDTH - PAD - 40, footerY - 34);
    ctx.stroke();

    ctx.fillStyle = COLORS.deep;
    setFont(ctx, 28, 600);
    ctx.fillText('للحجز والاستفسار', WIDTH - PAD - 40, footerY + 10);
    ctx.fillStyle = COLORS.body;
    ctx.direction = 'ltr';
    ctx.textAlign = 'right';
    setFont(ctx, 27, 500);
    ctx.fillText('01017900354  ·  01509011313', WIDTH - PAD - 40, footerY + 56);

    ctx.direction = 'rtl';
    ctx.fillStyle = COLORS.muted;
    setFont(ctx, 22, 400);
    ctx.fillText('بطاقة لترتيب أسئلتكِ قبل الزيارة', WIDTH - PAD - 40, footerY + 112);

    const blob = await new Promise((resolve, reject) => {
      canvas.toBlob((result) => {
        if (result) resolve(result);
        else reject(new Error('Could not create the journey card image.'));
      }, 'image/png');
    });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'رحلتك-مع-د-محمد-هلال.png';
    link.click();
    window.setTimeout(() => URL.revokeObjectURL(url), 1000);

    return { width: WIDTH, height, questionCount: safeQuestions.length };
  }

  window.saveHelalJourneyCard = saveHelalJourneyCard;
})();
