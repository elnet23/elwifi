
        // Global variables
        let vouchers = JSON.parse(localStorage.getItem('vouchers')) || [];
        let mikrotikSettings = JSON.parse(localStorage.getItem('mikrotikSettings')) || {
            ip: '192.168.1.1',
            username: 'admin',
            password: 'password',
            port: 8728,
            timeout: 10
        };
        let adminPassword = localStorage.getItem('adminPassword') || 'admin123';

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            updateVoucherStats();
            loadVouchersTable();
            loadMikrotikSettings();
            
            // Smooth scrolling
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    if (!this.onclick) {
                        e.preventDefault();
                        const target = document.querySelector(this.getAttribute('href'));
                        if (target) {
                            target.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });
                        }
                    }
                });
            });
        });

        // Admin Functions
        function showAdminLogin() {
            document.getElementById('adminLoginModal').style.display = 'block';
        }

        function showAdminSection(sectionId) {
            // Hide all sections
            document.querySelectorAll('.admin-section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Remove active class from all nav items
            document.querySelectorAll('.admin-nav li').forEach(item => {
                item.classList.remove('active');
            });
            
            // Show selected section
            document.getElementById('admin-' + sectionId).classList.add('active');
            
            // Add active class to clicked nav item
            event.target.closest('li').classList.add('active');
        }

        function loginAdmin(e) {
            e.preventDefault();
            const password = document.getElementById('adminPassword').value;
            
            if (password === adminPassword) {
                document.getElementById('mainWebsite').style.display = 'none';
                document.getElementById('adminDashboard').style.display = 'block';
                document.getElementById('adminLoginModal').style.display = 'none';
                updateVoucherStats();
            } else {
                alert('Password salah!');
            }
        }

        function logoutAdmin() {
            document.getElementById('mainWebsite').style.display = 'block';
            document.getElementById('adminDashboard').style.display = 'none';
        }

        // Voucher Management
        function generateVoucherCode(prefix = 'ELNET') {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            let result = prefix + '-';
            for (let i = 0; i < 8; i++) {
                result += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            return result;
        }

        function addVoucherToMikrotik(voucher, profile, callback) {
            // Simulasi koneksi ke MikroTik
            setTimeout(() => {
                // Di implementasi sebenarnya, ini akan menggunakan MikroTik API
                console.log(`Adding voucher ${voucher} to MikroTik with profile ${profile}`);
                callback(true);
            }, 1000);
        }

        function generateVouchers(e) {
            e.preventDefault();
            
            const package = document.getElementById('voucherPackage').value;
            const count = parseInt(document.getElementById('voucherCount').value);
            const prefix = document.getElementById('voucherPrefix').value || 'ELNET';
            const profile = document.getElementById('profileName').value;
            const ip = document.getElementById('mikrotikIP').value;
            const user = document.getElementById('mikrotikUser').value;
            const pass = document.getElementById('mikrotikPass').value;
            
            const generatedVouchers = [];
            let successCount = 0;
            
            for (let i = 0; i < count; i++) {
                const voucher = generateVoucherCode(prefix);
                const voucherData = {
                    code: voucher,
                    package: package,
                    profile: profile,
                    created: new Date().toISOString(),
                    expires: getExpiryDate(package),
                    status: 'active',
                    usedBy: null,
                    usedAt: null
                };
                
                // Simulasi penambahan ke MikroTik
                addVoucherToMikrotik(voucher, profile, (success) => {
                    if (success) {
                        vouchers.push(voucherData);
                        generatedVouchers.push(voucherData);
                        successCount++;
                        
                        if (successCount === count) {
                            // Save to localStorage
                            localStorage.setItem('vouchers', JSON.stringify(vouchers));
                            
                            // Display generated vouchers
                            displayGeneratedVouchers(generatedVouchers);
                            updateVoucherStats();
                            loadVouchersTable();
                            
                            // Add activity log
                            addActivity(`Generated ${count} voucher ${package}`);
                        }
                    }
                });
            }
        }

        function getExpiryDate(package) {
            const now = new Date();
            switch(package) {
                case '6 Jam':
                    return new Date(now.getTime() + 6 * 60 * 60 * 1000).toISOString();
                case '1 Hari':
                    return new Date(now.getTime() + 24 * 60 * 60 * 1000).toISOString();
                case '7 Hari':
                    return new Date(now.getTime() + 7 * 24 * 60 * 60 * 1000).toISOString();
                case '30 Hari':
                    return new Date(now.getTime() + 30 * 24 * 60 * 60 * 1000).toISOString();
                default:
                    return new Date(now.getTime() + 24 * 60 * 60 * 1000).toISOString();
            }
        }

        function displayGeneratedVouchers(voucherList) {
            const container = document.getElementById('generatedVouchers');
            const list = document.getElementById('vouchersList');
            
            list.innerHTML = voucherList.map(v => `
                <div style="padding: 10px; margin: 5px 0; background: white; border: 1px solid #ddd; border-radius: 5px; display: flex; justify-content: space-between; align-items: center;">
                    <span><strong>${v.code}</strong> - ${v.package}</span>
                    <span class="status-badge status-active">Aktif</span>