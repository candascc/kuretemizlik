<?php 
$isEdit = !empty($job);
?>
<div class="space-y-8" x-data="calendarJobForm()">
    <!-- Breadcrumb -->
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-4">
            <li><a href="<?= base_url('/calendar') ?>" class="text-gray-400 hover:text-primary-600"><i class="fas fa-calendar"></i></a></li>
            <li class="flex items-center"><i class="fas fa-chevron-right text-gray-400 mx-2"></i><span class="text-gray-500"><?= $isEdit ? 'Düzenle' : 'Yeni İş' ?></span></li>
        </ol>
    </nav>

    <!-- Header -->
    <div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
            <i class="fas fa-calendar mr-3 text-primary-600"></i>
            <?= $isEdit ? 'İşi Düzenle' : 'Yeni İş' ?>
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2"><?= $isEdit ? 'İş bilgilerini güncelleyin' : 'Yeni bir iş kaydı oluşturun' ?></p>
    </div>

    <?php include __DIR__ . '/../partials/flash.php'; ?>

    <!-- Form Container -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 overflow-hidden">
        <form method="POST" action="<?= $isEdit ? base_url("/calendar/update/{$job['id']}") : base_url('/calendar/create') ?>" 
              @submit="handleFormSubmit($event)" role="form" aria-describedby="calendar-form-errors" novalidate data-validate="true">
            <?= CSRF::field() ?>

            <!-- SECTION 1: Temel Bilgiler -->
            <div class="border-b border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 bg-gradient-to-r from-primary-50 to-blue-50 dark:from-primary-900/20 dark:to-blue-900/20">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-info-circle mr-2 text-primary-600"></i>
                        Temel Bilgiler
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Müşteri ve iş bilgileri</p>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6"><div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="relative">
                <label class="block text-sm text-gray-700 dark:text-gray-300">M�?Yteri</label>
                <input type="text" x-model="customerQuery" @input="searchCustomers" placeholder="Isim yazin..." class="mt-1 w-full border-2 border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" autocomplete="off">
                <input type="hidden" name="customer_id" :value="customerId" required aria-required="true" aria-invalid="false" data-validate="required|numeric|min:1">
                <div class="absolute z-10 bg-white dark:bg-gray-800 border border-gray-200 rounded mt-1 w-full max-h-48 overflow-auto" x-show="showCustomerList">
                    <template x-for="item in customerResults" :key="item.id">
                        <div class="px-4 py-3 hover:bg-gray-50 cursor-pointer" @click="selectCustomer(item)">
                            <span x-text="item.name"></span>
                        </div>
                    </template>
                    <div class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300" x-show="customerResults.length===0">Sonu� yok</div>
                    <div class="px-4 py-3 text-sm">
                        <button type="button" class="text-blue-600" @click="openNewCustomerModal">+ Yeni M�?Yteri</button>
                    </div>
                </div>
            </div>
            <div>
                <label class="block text-sm text-gray-700 dark:text-gray-300">Adres (opsiyonel)</label>
                <select name="address_id" class="mt-1 w-full border-2 border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" x-model="addressId">
                    <option value="">Se�iniz</option>
                    <template x-for="addr in addresses" :key="addr.id">
                        <option :value="addr.id" x-text="addr.label ? (addr.label + ' - ' + addr.line) : addr.line" :selected="addr.id == initialAddressId"></option>
                    </template>
                </select>
            </div>

            <div>
                <label class="block text-sm text-gray-700 dark:text-gray-300">Hizmet</label>
                <select name="service_id" class="mt-1 w-full border-2 border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" x-model.number="serviceId" @change="applyServiceDefaults()" aria-describedby="service-error service-hint" data-validate="numeric|min:1">
                    <option value="">Se�iniz</option>
                <p id="service-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                <p id="service-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">Opsiyonel: hizmet seçebilirsiniz</p>
                    <?php foreach ($services as $s): ?>
                        <option value="<?= $s['id'] ?>" data-duration="<?= (int)($s['duration_min'] ?? 0) ?>" data-fee="<?= (float)($s['default_fee'] ?? 0) ?>" <?= $isEdit && (int)($job['service_id'] ?? 0)===$s['id']?'selected':'' ?>><?= e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm text-gray-700 dark:text-gray-300">Ba?Ylangi�</label>
                <div class="flex gap-2">
                    <input type="datetime-local" name="start_at" x-model="startAt" class="mt-1 w-full border-2 border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" required @change="autoSetEnd()" aria-required="true" aria-invalid="false" aria-describedby="start_at-error start_at-hint" data-validate="required|datetime">
                    <p id="start_at-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                    <p id="start_at-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">Başlangıç tarih ve saatini seçiniz</p>
                    <button type="button" class="mt-1 px-2 py-1 text-sm rounded-lg bg-gray-100" @click="quickStart(60)">+1s</button>
                    <button type="button" class="mt-1 px-2 py-1 text-sm rounded-lg bg-gray-100" @click="quickStart(120)">+2s</button>
                </div>
            </div>
            <div>
                <label class="block text-sm text-gray-700 dark:text-gray-300">Biti?Y</label>
                <input type="datetime-local" name="end_at" x-model="endAt" class="mt-1 w-full border-2 border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" required aria-required="true" aria-invalid="false" aria-describedby="end_at-error end_at-hint" data-validate="required|datetime">
                <p id="end_at-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                <p id="end_at-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">Bitiş tarihi başlangıçtan sonra olmalıdır</p>
            </div>
        </div>

        <div>
            <label class="block text-sm text-gray-700 dark:text-gray-300">Not</label>
            <textarea name="note" class="mt-1 w-full border-2 border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" rows="3"><?= $isEdit ? htmlspecialchars($job['note'] ?? '') : '' ?></textarea>
        </div>

        <div class="flex justify-end gap-3">
            <a href="<?= base_url('/calendar') ?>" class="px-4 py-2 rounded-lg bg-gray-100 text-gray-700" :class="{ 'pointer-events-none': isSubmitting }">Iptal</a>
            <button type="submit" 
                    class="px-4 py-2 rounded-lg bg-primary-600 hover:bg-primary-700 relative font-medium"
                    style="color: white !important; background: #4f46e5 !important;"
                    :class="{ 'loading': isSubmitting }" 
                    :disabled="isSubmitting">
                <span x-show="!isSubmitting" style="color: white !important;">Kaydet</span>
                <span x-show="isSubmitting" class="flex items-center" style="color: white !important;">
                    <i class="fas fa-spinner fa-spin mr-2" style="color: white !important;"></i>
                    Kaydediliyor...
                </span>
            </button>
        </div>
    </form>
        </div>

<script>
function calendarJobForm() {
    return {
        isSubmitting: false,
        customerId: <?= $isEdit ? (int)$job['customer_id'] : 'null' ?>,
        customerQuery: '',
        customerResults: [],
        showCustomerList: false,
        addressId: <?= $isEdit && !empty($job['address_id']) ? (int)$job['address_id'] : 'null' ?>,
        initialAddressId: <?= $isEdit && !empty($job['address_id']) ? (int)$job['address_id'] : 'null' ?>,
        addresses: [],
        serviceId: <?= $isEdit ? (int)($job['service_id'] ?? 0) : 'null' ?>,
        serviceDuration: 0,
        startAt: '<?= $isEdit ? date('Y-m-d\TH:i', strtotime($job['start_at'])) : date('Y-m-d\TH:i') ?>',
        endAt: '<?= $isEdit ? date('Y-m-d\TH:i', strtotime($job['end_at'])) : date('Y-m-d\TH:i', strtotime('+2 hours')) ?>',
        
        init() {
            if (this.customerId) this.loadAddresses();
        },
        
        async searchCustomers() {
            if (this.customerQuery.length < 2) {
                this.customerResults = [];
                this.showCustomerList = false;
                return;
            }
            const res = await fetch(`<?= base_url('/api/search-customers') ?>?q=${encodeURIComponent(this.customerQuery)}`);
            const data = await res.json();
            if (data.success) {
                this.customerResults = data.data || [];
                this.showCustomerList = true;
            }
        },
        
        selectCustomer(item) {
            this.customerId = item.id; 
            this.customerQuery = item.name; 
            this.showCustomerList = false; 
            this.loadAddresses();
        },
        
        async openNewCustomerModal() {
            const name = prompt('M�?Yteri adi:');
            if (!name) return;
            const body = { name };
            const res = await fetch('<?= base_url('/api/customers') ?>', {
                method: 'POST', 
                headers: { 'Content-Type': 'application/json' }, 
                body: JSON.stringify(body)
            });
            const data = await res.json();
            if (data.success) {
                this.customerId = data.data.id; 
                this.customerQuery = name; 
                this.showCustomerList = false; 
                this.loadAddresses();
            } else {
                alert(data.error || 'Olu?Yturulamadi');
            }
        },
        
        async loadAddresses() {
            if (!this.customerId) {
                this.addresses = [];
                return;
            }
            const res = await fetch(`<?= base_url('/api/customers') ?>/${this.customerId}/addresses`);
            const data = await res.json();
            if (data.success) {
                this.addresses = data.data || [];
            }
        },
        
        applyServiceDefaults() {
            const sel = document.querySelector('select[name="service_id"]');
            const opt = sel ? sel.options[sel.selectedIndex] : null;
            if (!opt) return;
            const dur = parseInt(opt.getAttribute('data-duration') || '0', 10);
            this.serviceDuration = dur || 0;
            if (this.startAt && dur) this.endAt = this.addMinutes(this.startAt, dur);
        },
        
        autoSetEnd() {
            if (this.startAt && this.serviceDuration) this.endAt = this.addMinutes(this.startAt, this.serviceDuration);
        },
        
        quickStart(mins) {
            const now = new Date();
            now.setMinutes(now.getMinutes() + mins);
            this.startAt = this.toInputValue(now);
            this.autoSetEnd();
        },
        
        addMinutes(dtLocal, mins) {
            const d = new Date(dtLocal);
            d.setMinutes(d.getMinutes() + mins);
            return this.toInputValue(d);
        },
        
        toInputValue(d) {
            const pad = (n) => String(n).padStart(2, '0');
            const y = d.getFullYear();
            const m = pad(d.getMonth()+1);
            const day = pad(d.getDate());
            const h = pad(d.getHours());
            const min = pad(d.getMinutes());
            return `${y}-${m}-${day}T${h}:${min}`;
        }
    };
}
</script>

