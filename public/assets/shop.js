const delivery = document.getElementById('shipping_method');
const total = document.getElementById('checkout-total');
function updateTotal() {
  if (!delivery || !total) return;
  const cod=document.querySelector('#payment_method option[value=cod]');if(cod)cod.textContent=delivery.selectedOptions[0]?.dataset.type==='pickup'?'Plaćanje pri ličnom preuzimanju':'Pouzećem';
  const amount = Number(total.dataset.subtotal) + Number(delivery.selectedOptions[0]?.dataset.cost || 0);
  total.textContent = new Intl.NumberFormat('bs-BA', {minimumFractionDigits:2,maximumFractionDigits:2}).format(amount / 100) + ' KM';
}
delivery?.addEventListener('change', updateTotal);
updateTotal();

document.querySelectorAll('[data-carousel]').forEach(carousel => {
  const slides = [...carousel.querySelectorAll('[data-slide]')];
  const controls = carousel.querySelector('[data-carousel-controls]');
  if (slides.length < 2 || !controls) return;
  controls.hidden = false;
  const dots = [...controls.querySelectorAll('[data-slide-index]')];
  const pauseButton = controls.querySelector('[data-carousel-pause]');
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
  const interval = Math.max(2000, Math.min(30000, Number(carousel.dataset.interval) || 6000));
  const duration = Math.max(150, Math.min(1200, Number(carousel.dataset.duration) || 500));
  const effect = ['fade','slide','none'].includes(carousel.dataset.effect) ? carousel.dataset.effect : 'fade';
  let index = 0, paused = reduced.matches || carousel.dataset.autoplay === '0', hovered = false, focused = false, timer, animation;
  function warmNext() { slides[(index + 1) % slides.length].querySelectorAll('img').forEach(image => { image.loading = 'eager'; }); }
  function show(next, announce = false) {
    animation?.cancel(); index = (next + slides.length) % slides.length;
    slides.forEach((slide, i) => { slide.hidden = i !== index; });
    dots.forEach((dot, i) => dot.setAttribute('aria-pressed', String(i === index)));
    if (!reduced.matches && effect !== 'none' && slides[index].animate) animation = slides[index].animate(effect === 'slide' ? [{opacity:.15,transform:'translateX(8%)'},{opacity:1,transform:'translateX(0)'}] : [{opacity:0},{opacity:1}], {duration,easing:'ease-out'});
    if (announce) { const status = controls.querySelector('[data-carousel-status]'); if (status) status.textContent = 'Objava ' + (index + 1) + ' od ' + slides.length; }
    warmNext();
  }
  function schedule() {
    clearInterval(timer);
    if (!paused && !hovered && !focused && !document.hidden) timer = setInterval(() => show(index + 1), interval);
    if (pauseButton) { pauseButton.textContent = paused ? '▶' : 'Ⅱ'; pauseButton.setAttribute('aria-label', paused ? 'Pokreni automatsku izmjenu' : 'Zaustavi automatsku izmjenu'); pauseButton.setAttribute('aria-pressed', String(paused)); }
  }
  dots.forEach((dot, i) => dot.addEventListener('click', () => { show(i, true); schedule(); }));
  controls.querySelector('[data-carousel-prev]')?.addEventListener('click', () => { show(index - 1, true); schedule(); });
  controls.querySelector('[data-carousel-next]')?.addEventListener('click', () => { show(index + 1, true); schedule(); });
  pauseButton?.addEventListener('click', () => { paused = !paused; schedule(); });
  carousel.addEventListener('mouseenter', () => { hovered = carousel.dataset.hoverPause !== '0'; schedule(); });
  carousel.addEventListener('mouseleave', () => { hovered = false; schedule(); });
  carousel.addEventListener('focusin', () => { focused = true; schedule(); });
  carousel.addEventListener('focusout', event => { if (!carousel.contains(event.relatedTarget)) { focused = false; schedule(); } });
  carousel.addEventListener('keydown', event => { if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') { event.preventDefault(); show(index + (event.key === 'ArrowRight' ? 1 : -1), true); schedule(); } });
  let pointer, suppressClick = false;
  carousel.addEventListener('pointerdown', event => { if (event.pointerType === 'touch') pointer = {x:event.clientX,y:event.clientY}; });
  carousel.addEventListener('pointerup', event => { if (!pointer) return; const dx=event.clientX-pointer.x,dy=event.clientY-pointer.y; pointer=null; if (Math.abs(dx)>60 && Math.abs(dx)>Math.abs(dy)*1.3) { suppressClick=true; show(index+(dx<0?1:-1),true); schedule(); } });
  carousel.addEventListener('pointercancel', () => { pointer=null; });
  carousel.addEventListener('click', event => { if(suppressClick){event.preventDefault();suppressClick=false;} },true);
  document.addEventListener('visibilitychange', schedule);
  reduced.addEventListener('change', event => { if (event.matches) { paused = true; animation?.cancel(); schedule(); } });
  warmNext(); schedule();
});

document.querySelectorAll('[data-gallery]').forEach(gallery => {
  const buttons = [...gallery.querySelectorAll('[data-gallery-src]')];
  const link = gallery.querySelector('[data-gallery-link]');
  const main = gallery.querySelector('[data-gallery-main]');
  const sources = buttons.length ? buttons.map(b => b.dataset.gallerySrc) : [main.getAttribute('src')];
  const dialog = gallery.querySelector('[data-lightbox]');
  let index = 0, savedOverflow = '';
  function select(next) {
    index = (next + sources.length) % sources.length;
    main.src = sources[index]; link.href = sources[index];
    buttons.forEach((b, i) => b.setAttribute('aria-pressed', String(i === index)));
    if (dialog) { dialog.querySelector('[data-lightbox-image]').src = sources[index]; dialog.querySelector('[data-lightbox-counter]').textContent = (index + 1) + ' / ' + sources.length; }
  }
  buttons.forEach((button, i) => button.addEventListener('click', () => select(i)));
  if (!dialog || typeof dialog.showModal !== 'function') return;
  dialog.querySelector('.lightbox-stage').classList.toggle('is-single', sources.length < 2);
  link.addEventListener('click', event => { event.preventDefault(); select(index); savedOverflow = document.body.style.overflow; document.body.style.overflow = 'hidden'; dialog.showModal(); });
  dialog.querySelector('[data-lightbox-close]').addEventListener('click', () => dialog.close());
  dialog.querySelector('[data-lightbox-prev]').addEventListener('click', () => select(index - 1));
  dialog.querySelector('[data-lightbox-next]').addEventListener('click', () => select(index + 1));
  for (const button of dialog.querySelectorAll('[data-lightbox-prev],[data-lightbox-next]')) button.hidden = sources.length < 2;
  dialog.addEventListener('keydown', event => { if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') { event.preventDefault(); select(index + (event.key === 'ArrowRight' ? 1 : -1)); } });
  dialog.addEventListener('click', event => { if (event.target === dialog) { const r = dialog.getBoundingClientRect(); if(event.clientX < r.left || event.clientX > r.right || event.clientY < r.top || event.clientY > r.bottom) dialog.close(); } });
  dialog.addEventListener('close', () => { document.body.style.overflow = savedOverflow; link.focus(); });
});
document.querySelectorAll('[data-quantity]').forEach(button => button.addEventListener('click', () => {
  const input = button.closest('.quantity-control').querySelector('input');
  input.value = String(Math.max(Number(input.min), Math.min(Number(input.max), Number(input.value || 1) + Number(button.dataset.quantity))));
}));
