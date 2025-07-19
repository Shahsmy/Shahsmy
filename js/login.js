// Login page functionality
document.addEventListener('DOMContentLoaded', function() {
    // Check if already logged in
    checkExistingSession();
    
    // Setup form submission
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }
    
    // Setup remember me checkbox
    loadRememberedCredentials();
    
    // Setup enter key handling
    document.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            handleLogin(e);
        }
    });
});

async function checkExistingSession() {
    try {
        const isValid = await auth.checkSession();
        if (isValid) {
            // Redirect to dashboard
            window.location.href = 'dashboard.html';
        }
    } catch (error) {
        console.log('No existing session');
    }
}

async function handleLogin(e) {
    e.preventDefault();
    
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value;
    const remember = document.getElementById('remember').checked;
    
    // Validate inputs
    if (!username || !password) {
        showError('لطفاً نام کاربری و رمز عبور را وارد کنید');
        return;
    }
    
    // Show loading state
    setLoginLoading(true);
    
    try {
        const result = await auth.login(username, password);
        
        if (result.success) {
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
            showError(result.message || 'خطا در ورود به سیستم');
            // Shake form on error
            shakeForm();
        }
    } catch (error) {
        console.error('Login error:', error);
        showError('خطا در ارتباط با سرور');
        shakeForm();
    } finally {
        setLoginLoading(false);
    }
}

function setLoginLoading(loading) {
    const loginBtn = document.getElementById('loginBtn');
    const loadingOverlay = document.getElementById('loadingOverlay');
    
    if (loading) {
        loginBtn.disabled = true;
        loginBtn.classList.add('loading');
        loginBtn.innerHTML = '<div class="spinner"></div> در حال ورود...';
        
        if (loadingOverlay) {
            loadingOverlay.style.display = 'flex';
        }
    } else {
        loginBtn.disabled = false;
        loginBtn.classList.remove('loading');
        loginBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i> ورود';
        
        if (loadingOverlay) {
            loadingOverlay.style.display = 'none';
        }
    }
}

function showError(message) {
    hideAllMessages();
    const errorDiv = document.getElementById('errorMessage');
    if (errorDiv) {
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
        errorDiv.classList.add('fade-in');
    }
}

function showSuccess(message) {
    hideAllMessages();
    const successDiv = document.getElementById('successMessage');
    if (successDiv) {
        successDiv.textContent = message;
        successDiv.style.display = 'block';
        successDiv.classList.add('fade-in');
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
            icon.className = 'fas fa-eye-slash';
        } else {
            passwordField.type = 'password';
            icon.className = 'fas fa-eye';
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

// Initialize input validation when DOM loads
document.addEventListener('DOMContentLoaded', setupInputValidation);

// Demo credentials auto-fill (for development)
function fillDemoCredentials() {
    document.getElementById('username').value = 'admin';
    document.getElementById('password').value = 'admin123';
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