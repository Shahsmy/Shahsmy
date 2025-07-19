// Login page functionality
document.addEventListener('DOMContentLoaded', function() {
    console.log('Login page loaded'); // دیباگ
    
    // Check if already logged in
    checkExistingSession();
    
    // Setup form submission
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
        console.log('Login form event listener added'); // دیباگ
    } else {
        console.error('Login form not found!');
    }
    
    // Setup remember me checkbox
    loadRememberedCredentials();
    
    // Setup enter key handling
    document.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            const form = document.getElementById('loginForm');
            if (form && document.activeElement && form.contains(document.activeElement)) {
                handleLogin(e);
            }
        }
    });
    
    // Setup input validation
    setupInputValidation();
});

async function checkExistingSession() {
    try {
        console.log('Checking existing session...'); // دیباگ
        const isValid = await auth.checkSession();
        if (isValid) {
            console.log('Valid session found, redirecting to dashboard'); // دیباگ
            window.location.href = 'dashboard.html';
        } else {
            console.log('No valid session found'); // دیباگ
        }
    } catch (error) {
        console.log('No existing session:', error);
    }
}

async function handleLogin(e) {
    e.preventDefault();
    console.log('Login form submitted'); // دیباگ
    
    const username = document.getElementById('username')?.value?.trim();
    const password = document.getElementById('password')?.value;
    const remember = document.getElementById('remember')?.checked;
    
    console.log('Login data:', { username, password: password ? '[PROVIDED]' : '[EMPTY]', remember }); // دیباگ
    
    // Validate inputs
    if (!username || !password) {
        showError('لطفاً نام کاربری و رمز عبور را وارد کنید');
        return;
    }
    
    // Show loading state
    setLoginLoading(true);
    
    try {
        console.log('Attempting login...'); // دیباگ
        const result = await auth.login(username, password);
        console.log('Login result:', result); // دیباگ
        
        if (result && result.success) {
            // Handle remember me
            if (remember) {
                localStorage.setItem('rememberedUsername', username);
            } else {
                localStorage.removeItem('rememberedUsername');
            }
            
            showSuccess('ورود موفقیت‌آمیز، در حال انتقال...');
            
            // Redirect to dashboard after short delay
            setTimeout(() => {
                window.location.href = 'dashboard.html';
            }, 1000);
            
        } else {
            const errorMessage = result?.message || 'خطا در ورود به سیستم';
            showError(errorMessage);
            shakeForm();
        }
    } catch (error) {
        console.error('Login error:', error);
        showError(error.message || 'خطا در ارتباط با سرور');
        shakeForm();
    } finally {
        setLoginLoading(false);
    }
}

function setLoginLoading(loading) {
    const loginBtn = document.getElementById('loginBtn');
    const loadingOverlay = document.getElementById('loadingOverlay');
    
    if (loginBtn) {
        if (loading) {
            loginBtn.disabled = true;
            loginBtn.classList.add('loading');
            loginBtn.innerHTML = '<div class="spinner"></div> در حال ورود...';
        } else {
            loginBtn.disabled = false;
            loginBtn.classList.remove('loading');
            loginBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i> ورود';
        }
    }
    
    if (loadingOverlay) {
        loadingOverlay.style.display = loading ? 'flex' : 'none';
    }
}

function showError(message) {
    console.log('Showing error:', message); // دیباگ
    hideAllMessages();
    
    // Use the global UI function first
    if (window.ui && window.ui.showError) {
        ui.showError(message);
        return;
    }
    
    // Fallback to local error display
    const errorDiv = document.getElementById('errorMessage');
    if (errorDiv) {
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
        errorDiv.classList.add('fade-in');
    } else {
        // Create temporary error message
        const tempError = document.createElement('div');
        tempError.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
            padding: 1rem;
            border-radius: 8px;
            z-index: 10000;
            max-width: 400px;
        `;
        tempError.textContent = message;
        document.body.appendChild(tempError);
        
        setTimeout(() => {
            if (tempError.parentNode) {
                tempError.remove();
            }
        }, 5000);
    }
}

function showSuccess(message) {
    console.log('Showing success:', message); // دیباگ
    hideAllMessages();
    
    // Use the global UI function first
    if (window.ui && window.ui.showSuccess) {
        ui.showSuccess(message);
        return;
    }
    
    // Fallback to local success display
    const successDiv = document.getElementById('successMessage');
    if (successDiv) {
        successDiv.textContent = message;
        successDiv.style.display = 'block';
        successDiv.classList.add('fade-in');
    } else {
        // Create temporary success message
        const tempSuccess = document.createElement('div');
        tempSuccess.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #f0fdf4;
            color: #059669;
            border: 1px solid #bbf7d0;
            padding: 1rem;
            border-radius: 8px;
            z-index: 10000;
            max-width: 400px;
        `;
        tempSuccess.textContent = message;
        document.body.appendChild(tempSuccess);
        
        setTimeout(() => {
            if (tempSuccess.parentNode) {
                tempSuccess.remove();
            }
        }, 3000);
    }
}

function hideAllMessages() {
    const errorDiv = document.getElementById('errorMessage');
    const successDiv = document.getElementById('successMessage');
    
    if (errorDiv) {
        errorDiv.style.display = 'none';
        errorDiv.classList.remove('fade-in');
    }
    
    if (successDiv) {
        successDiv.style.display = 'none';
        successDiv.classList.remove('fade-in');
    }
}

function shakeForm() {
    const form = document.getElementById('loginForm');
    if (form) {
        // Add error class to form groups
        const formGroups = form.querySelectorAll('.form-group');
        formGroups.forEach(group => {
            group.classList.add('error');
            setTimeout(() => {
                group.classList.remove('error');
            }, 500);
        });
    }
}

function loadRememberedCredentials() {
    const rememberedUsername = localStorage.getItem('rememberedUsername');
    if (rememberedUsername) {
        const usernameField = document.getElementById('username');
        const rememberCheckbox = document.getElementById('remember');
        
        if (usernameField) {
            usernameField.value = rememberedUsername;
        }
        
        if (rememberCheckbox) {
            rememberCheckbox.checked = true;
        }
        
        // Focus on password field if username is filled
        const passwordField = document.getElementById('password');
        if (passwordField) {
            passwordField.focus();
        }
    } else {
        // Focus on username field
        const usernameField = document.getElementById('username');
        if (usernameField) {
            usernameField.focus();
        }
    }
}

function togglePassword() {
    const passwordField = document.getElementById('password');
    const toggleBtn = document.querySelector('.toggle-password');
    
    if (passwordField && toggleBtn) {
        const icon = toggleBtn.querySelector('i');
        
        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            if (icon) icon.className = 'fas fa-eye-slash';
        } else {
            passwordField.type = 'password';
            if (icon) icon.className = 'fas fa-eye';
        }
    }
}

// Input validation
function setupInputValidation() {
    const usernameField = document.getElementById('username');
    const passwordField = document.getElementById('password');
    
    if (usernameField) {
        usernameField.addEventListener('blur', validateUsername);
        usernameField.addEventListener('input', clearFieldError);
    }
    
    if (passwordField) {
        passwordField.addEventListener('blur', validatePassword);
        passwordField.addEventListener('input', clearFieldError);
    }
}

function validateUsername() {
    const usernameField = document.getElementById('username');
    if (!usernameField) return true;
    
    const value = usernameField.value.trim();
    
    if (!value) {
        setFieldError(usernameField, 'نام کاربری الزامی است');
        return false;
    }
    
    if (value.length < 3) {
        setFieldError(usernameField, 'نام کاربری باید حداقل ۳ کاراکتر باشد');
        return false;
    }
    
    clearFieldError(usernameField);
    return true;
}

function validatePassword() {
    const passwordField = document.getElementById('password');
    if (!passwordField) return true;
    
    const value = passwordField.value;
    
    if (!value) {
        setFieldError(passwordField, 'رمز عبور الزامی است');
        return false;
    }
    
    if (value.length < 6) {
        setFieldError(passwordField, 'رمز عبور باید حداقل ۶ کاراکتر باشد');
        return false;
    }
    
    clearFieldError(passwordField);
    return true;
}

function setFieldError(field, message) {
    const formGroup = field.closest('.form-group');
    if (formGroup) {
        formGroup.classList.add('error');
        
        // Remove existing error message
        const existingError = formGroup.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }
        
        // Add new error message
        const errorEl = document.createElement('div');
        errorEl.className = 'field-error';
        errorEl.textContent = message;
        errorEl.style.color = 'var(--danger-color)';
        errorEl.style.fontSize = '0.75rem';
        errorEl.style.marginTop = '0.25rem';
        
        formGroup.appendChild(errorEl);
    }
}

function clearFieldError(field) {
    if (typeof field === 'object' && field.target) {
        field = field.target;
    }
    
    const formGroup = field.closest('.form-group');
    if (formGroup) {
        formGroup.classList.remove('error');
        
        const errorEl = formGroup.querySelector('.field-error');
        if (errorEl) {
            errorEl.remove();
        }
    }
}

// Demo credentials auto-fill (for development)
function fillDemoCredentials() {
    const usernameField = document.getElementById('username');
    const passwordField = document.getElementById('password');
    
    if (usernameField) usernameField.value = 'admin';
    if (passwordField) passwordField.value = 'admin123';
    
    console.log('Demo credentials filled'); // دیباگ
}

// Add click handler for demo credentials
document.addEventListener('DOMContentLoaded', function() {
    const demoCredentials = document.querySelector('.demo-credentials');
    if (demoCredentials) {
        demoCredentials.addEventListener('click', fillDemoCredentials);
        demoCredentials.style.cursor = 'pointer';
        demoCredentials.title = 'کلیک کنید تا اطلاعات ورود به صورت خودکار پر شود';
    }
});

// Handle browser back button
window.addEventListener('pageshow', function(event) {
    if (event.persisted) {
        // Page was loaded from cache, check session again
        checkExistingSession();
    }
});

// Prevent form resubmission on page refresh
if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
}

// Test function for debugging login
window.testLogin = function() {
    console.log('Testing login with demo credentials...');
    fillDemoCredentials();
    
    setTimeout(() => {
        const form = document.getElementById('loginForm');
        if (form) {
            const event = new Event('submit', { cancelable: true });
            form.dispatchEvent(event);
        }
    }, 1000);
};