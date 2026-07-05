<div class="row g-3">
    <div class="col-lg-6">
        <div class="surface-card p-4 text-center">
            <h6 class="fw-bold mb-3"><i class="fa-solid fa-calendar-check text-success me-2"></i>Daily Check-In</h6>
            <p class="text-muted-soft small">Check in every day to build your streak and earn bigger bonuses.</p>
            <div class="d-flex justify-content-center gap-2 my-3">
                <?php for ($i = 1; $i <= 7; $i++): ?>
                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width:34px;height:34px;font-size:.75rem; background:<?= $i <= $streak ? 'var(--c-accent)' : 'var(--bg-surface-alt)' ?>; color:<?= $i <= $streak ? '#1a1300' : 'var(--text-muted)' ?>;">
                        <?= $i ?>
                    </div>
                <?php endfor; ?>
            </div>
            <button id="checkinBtn" class="btn btn-brand rounded-pill px-4" <?= $checkedInToday ? 'disabled' : '' ?>>
                <?= $checkedInToday ? 'Checked In Today ✓' : 'Check In Now' ?>
            </button>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="surface-card p-4 text-center">
            <h6 class="fw-bold mb-3"><i class="fa-solid fa-dharmachakra text-warning me-2"></i>Spin the Wheel</h6>
            <div class="spin-wheel-wrap mb-3">
                <svg id="spinWheel" class="spin-wheel" width="220" height="220" viewBox="0 0 220 220">
                    <?php
                    $colors = ['#0D47A1', '#1565C0', '#FFC107', '#0D47A1', '#1565C0', '#FFC107', '#0D47A1', '#1565C0'];
                    $segments = count($colors);
                    $angle = 360 / $segments;
                    for ($i = 0; $i < $segments; $i++):
                        $start = deg2rad($i * $angle - 90);
                        $end = deg2rad(($i + 1) * $angle - 90);
                        $x1 = 110 + 105 * cos($start); $y1 = 110 + 105 * sin($start);
                        $x2 = 110 + 105 * cos($end); $y2 = 110 + 105 * sin($end);
                    ?>
                        <path d="M110,110 L<?= $x1 ?>,<?= $y1 ?> A105,105 0 0,1 <?= $x2 ?>,<?= $y2 ?> Z" fill="<?= $colors[$i] ?>" stroke="#fff" stroke-width="2"/>
                    <?php endfor; ?>
                    <circle cx="110" cy="110" r="18" fill="#fff" stroke="var(--c-primary)" stroke-width="3"/>
                </svg>
                <div style="position:absolute; top:-6px; left:50%; transform:translateX(-50%); font-size:1.5rem;">🔻</div>
            </div>
            <button id="spinBtn" class="btn btn-accent rounded-pill px-4" <?= $spunToday ? 'disabled' : '' ?>>
                <?= $spunToday ? 'Come Back Tomorrow' : 'Spin Now' ?>
            </button>
        </div>
    </div>
</div>

<?php push_script('
document.getElementById("checkinBtn")?.addEventListener("click", async function () {
    var btn = this;
    btn.disabled = true;
    var res = await NineJC.post("' . base_url('rewards/checkin') . '", {});
    NineJC.toast(res.success ? "success" : "error", res.message);
    if (!res.success) btn.disabled = false;
    else setTimeout(() => location.reload(), 1200);
});

document.getElementById("spinBtn")?.addEventListener("click", async function () {
    var btn = this;
    btn.disabled = true;
    var wheel = document.getElementById("spinWheel");
    var rotation = 1440 + Math.floor(Math.random() * 360);
    wheel.style.transform = "rotate(" + rotation + "deg)";
    var res = await NineJC.post("' . base_url('rewards/spin') . '", {});
    setTimeout(function () {
        NineJC.toast(res.success ? "success" : "error", res.message);
        if (!res.success) btn.disabled = false;
    }, 4100);
});
'); ?>
