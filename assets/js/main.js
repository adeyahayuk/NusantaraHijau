/**
 * NUSANTARAHIJAU VANILLA JAVASCRIPT
 * Interaktivitas Native: Donasi, Countdown, Grafik SVG, Modal & Tabs
 */

document.addEventListener('DOMContentLoaded', function () {
  // 1. COUNTDOWN TIMER PEMBAYARAN (24 JAM)
  const countdownEl = document.getElementById('paymentCountdown');
  if (countdownEl) {
    let totalSeconds = 23 * 3600 + 59 * 60 + 44; // Mulai dari 23:59:44
    const timerInterval = setInterval(function () {
      if (totalSeconds <= 0) {
        clearInterval(timerInterval);
        countdownEl.innerText = "00 : 00 : 00 (Kadaluarsa)";
        return;
      }
      totalSeconds--;
      const hours = String(Math.floor(totalSeconds / 3600)).padStart(2, '0');
      const minutes = String(Math.floor((totalSeconds % 3600) / 60)).padStart(2, '0');
      const seconds = String(totalSeconds % 60).padStart(2, '0');
      countdownEl.innerText = `${hours} : ${minutes} : ${seconds}`;
    }, 1000);
  }

  // 2. PILIHAN NOMINAL CEPAT & NOMINAL BEBAS (MODUL 6 - FORM DONASI)
  const amountButtons = document.querySelectorAll('.btn-amount');
  const amountInput = document.getElementById('customAmountInput');

  if (amountButtons.length > 0 && amountInput) {
    amountButtons.forEach(btn => {
      btn.addEventListener('click', function () {
        amountButtons.forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        const val = this.getAttribute('data-amount');
        amountInput.value = formatNumber(val);
      });
    });

    amountInput.addEventListener('input', function () {
      const rawVal = this.value.replace(/[^0-9]/g, '');
      this.value = rawVal ? formatNumber(rawVal) : '';
      
      // Update state tombol cepat jika nilainya cocok
      let matched = false;
      amountButtons.forEach(btn => {
        if (btn.getAttribute('data-amount') === rawVal) {
          btn.classList.add('active');
          matched = true;
        } else {
          btn.classList.remove('active');
        }
      });
    });
  }

  // 3. CHECKBOX DONASI SEBAGAI ANONIM
  const anonCheckbox = document.getElementById('anonCheckbox');
  const donorNameInput = document.getElementById('donorNameInput');
  if (anonCheckbox && donorNameInput) {
    anonCheckbox.addEventListener('change', function () {
      if (this.checked) {
        donorNameInput.setAttribute('data-prev-val', donorNameInput.value);
        donorNameInput.value = 'Hamba Allah (Anonim)';
        donorNameInput.readOnly = true;
      } else {
        donorNameInput.value = donorNameInput.getAttribute('data-prev-val') || '';
        donorNameInput.readOnly = false;
      }
    });
  }

  // 4. HITUNG KARAKTER PESAN / DOA
  const messageInput = document.getElementById('donorMessageInput');
  const charCounter = document.getElementById('charCounter');
  if (messageInput && charCounter) {
    messageInput.addEventListener('input', function () {
      const len = this.value.length;
      charCounter.innerText = `${len}/200`;
    });
  }

  // 5. PILIHAN METODE PEMBAYARAN KARTU RADIO
  const methodCards = document.querySelectorAll('.method-card');
  if (methodCards.length > 0) {
    const updatePaymentAction = function () {
      const selectedMethod = document.querySelector('input[name="payment_method"]:checked');
      const actionLabel = document.getElementById('paymentActionLabel');
      const labels = {
        qris: 'Lanjut ke Pembayaran QRIS',
        va_bank: 'Lanjut ke Virtual Account',
        ewallet: 'Lanjut ke E-Wallet'
      };

      if (selectedMethod && actionLabel) {
        actionLabel.textContent = labels[selectedMethod.value] || labels.qris;
      }
    };

    updatePaymentAction();
    methodCards.forEach(card => {
      card.addEventListener('click', function () {
        methodCards.forEach(c => c.classList.remove('selected'));
        this.classList.add('selected');
        const radio = this.querySelector('input[type="radio"]');
        if (radio) radio.checked = true;
        updatePaymentAction();
      });
    });
  }

  // 6. TAB SWITCHER DI HALAMAN PEMBAYARAN (QRIS / VA / EWALLET)
  const payTabBtns = document.querySelectorAll('.method-tab-btn');
  const payTabPanes = document.querySelectorAll('.payment-pane');
  if (payTabBtns.length > 0) {
    payTabBtns.forEach(btn => {
      btn.addEventListener('click', function () {
        payTabBtns.forEach(b => b.classList.remove('active'));
        payTabPanes.forEach(p => p.style.display = 'none');
        this.classList.add('active');
        const targetId = this.getAttribute('data-target');
        const targetPane = document.getElementById(targetId);
        if (targetPane) targetPane.style.display = 'block';
      });
    });
  }

  // 7. RENDER GRAFIK NATIVE SVG (ADMIN DASHBOARD)
  renderAdminCharts();

  // 8. PENCARIAN & FILTER TABEL
  const tableSearchInput = document.getElementById('tableSearchInput');
  if (tableSearchInput) {
    tableSearchInput.addEventListener('keyup', function () {
      const query = this.value.toLowerCase();
      const rows = document.querySelectorAll('.data-table tbody tr');
      rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(query) ? '' : 'none';
      });
    });
  }
});

// Helper format angka ribuan Indonesia
function formatNumber(num) {
  return parseInt(num, 10).toLocaleString('id-ID');
}

// Modal Helper
function openModal(modalId) {
  const m = document.getElementById(modalId);
  if (m) m.classList.add('open');
}

function closeModal(modalId) {
  const m = document.getElementById(modalId);
  if (m) m.classList.remove('open');
}

// Copy to clipboard
function copyToClipboard(text, alertMsg = 'Berhasil disalin ke clipboard!') {
  if (navigator.clipboard) {
    navigator.clipboard.writeText(text).then(() => alert(alertMsg));
  } else {
    const tempInput = document.createElement('input');
    tempInput.value = text;
    document.body.appendChild(tempInput);
    tempInput.select();
    document.execCommand('copy');
    document.body.removeChild(tempInput);
    alert(alertMsg);
  }
}

// RENDER GRAFIK DANA & SDG MENGGUNAKAN NATIVE SVG (ZERO FRAMEWORK)
function renderAdminCharts() {
  const barChartContainer = document.getElementById('adminBarChartSvg');
  if (barChartContainer) {
    // Data Komparasi Dana Masuk vs Penyaluran (Mei - Okt 2024)
    const months = ['Mei 24', 'Jun 24', 'Jul 24', 'Agu 24', 'Sep 24', 'Okt 24'];
    const masuk = [250, 280, 310, 330, 360, 395];      // dalam Juta
    const penyaluran = [190, 210, 260, 290, 320, 340]; // dalam Juta
    const maxVal = 400;

    let svgHtml = `
      <svg viewBox="0 0 540 220" width="100%" height="200" style="overflow: visible;">
        <!-- Gridlines -->
        <line x1="45" y1="20" x2="520" y2="20" stroke="#f1f5f9" stroke-dasharray="4" />
        <text x="35" y="24" font-size="10" fill="#94a3b8" text-anchor="end">400M</text>
        
        <line x1="45" y1="65" x2="520" y2="65" stroke="#f1f5f9" stroke-dasharray="4" />
        <text x="35" y="69" font-size="10" fill="#94a3b8" text-anchor="end">300M</text>
        
        <line x1="45" y1="110" x2="520" y2="110" stroke="#f1f5f9" stroke-dasharray="4" />
        <text x="35" y="114" font-size="10" fill="#94a3b8" text-anchor="end">200M</text>
        
        <line x1="45" y1="155" x2="520" y2="155" stroke="#f1f5f9" stroke-dasharray="4" />
        <text x="35" y="159" font-size="10" fill="#94a3b8" text-anchor="end">100M</text>
        
        <line x1="45" y1="175" x2="520" y2="175" stroke="#cbd5e1" stroke-width="1.5" />
        <text x="35" y="178" font-size="10" fill="#94a3b8" text-anchor="end">0M</text>
    `;

    const chartHeight = 155;
    const startY = 175;
    const groupWidth = 72;

    months.forEach((m, i) => {
      const groupX = 65 + i * groupWidth;
      const hMasuk = (masuk[i] / maxVal) * chartHeight;
      const hSalur = (penyaluran[i] / maxVal) * chartHeight;

      // Bar Dana Masuk (Deep Blue/Teal)
      svgHtml += `
        <rect x="${groupX}" y="${startY - hMasuk}" width="16" height="${hMasuk}" fill="#0284c7" rx="3" />
        <!-- Bar Penyaluran (Emerald Green) -->
        <rect x="${groupX + 18}" y="${startY - hSalur}" width="16" height="${hSalur}" fill="#10b981" rx="3" />
        <!-- Label Bulan -->
        <text x="${groupX + 17}" y="195" font-size="11" fill="#64748b" text-anchor="middle" font-weight="600">${m}</text>
      `;
    });

    svgHtml += `</svg>`;
    barChartContainer.innerHTML = svgHtml;
  }

  // Donut Chart Rasio Kategori SDGs
  const donutContainer = document.getElementById('adminDonutSvg');
  if (donutContainer) {
    // 64% SDG 14 (Laut), 36% SDG 15 (Darat)
    // Keliling lingkaran r=45 adalah 2 * PI * 45 = 282.74
    const circ = 282.74;
    const stroke14 = circ * 0.64; // ~180.95
    const stroke15 = circ * 0.36; // ~101.78

    const svgDonut = `
      <svg viewBox="0 0 160 160" width="140" height="140" style="transform: rotate(-90deg);">
        <!-- Background Circle -->
        <circle cx="80" cy="80" r="45" fill="transparent" stroke="#f1f5f9" stroke-width="18" />
        <!-- Segmen SDG 14: 64% (Ocean Blue) -->
        <circle cx="80" cy="80" r="45" fill="transparent" stroke="#0284c7" stroke-width="18"
          stroke-dasharray="${stroke14} ${circ}" stroke-dashoffset="0" />
        <!-- Segmen SDG 15: 36% (Forest Green) -->
        <circle cx="80" cy="80" r="45" fill="transparent" stroke="#10b981" stroke-width="18"
          stroke-dasharray="${stroke15} ${circ}" stroke-dashoffset="-${stroke14}" />
      </svg>
    `;
    donutContainer.innerHTML = svgDonut;
  }
}
