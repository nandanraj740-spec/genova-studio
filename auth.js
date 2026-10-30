// Store users in localStorage
const users = JSON.parse(localStorage.getItem('users')) || [];

// Check if user is logged in
function isLoggedIn() {
    return localStorage.getItem('currentUser') !== null;
}

// Redirect to login if not authenticated
function checkAuth() {
    if (!isLoggedIn()) {
        window.location.href = 'login.html';
    }
}

// Function to redirect using form submission
function redirectPost(location) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = location;
    document.body.appendChild(form);
    form.submit();
}

// Handle signup form submission
if (document.getElementById('signupForm')) {
    document.getElementById('signupForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const username = document.getElementById('username').value;
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirmPassword').value;

        // Basic validation
        if (password !== confirmPassword) {
            alert('Passwords do not match!');
            return;
        }

        // Create form data
        const formData = new FormData();
        formData.append('username', username);
        formData.append('email', email);
        formData.append('password', password);

        // Send signup request to PHP backend
        fetch('process_signup.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new TypeError("Expected JSON response but got " + contentType);
            }
            return response.json();
        })
        .then(data => {
            alert(data.message);
            if (data.success) {
                window.location.href = 'login.html';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again later.');
        });
    });
}

// Handle login form submission
if (document.getElementById('loginForm')) {
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;

        // Create form data
        const formData = new FormData();
        formData.append('username', username);
        formData.append('password', password);

        // Send login request to PHP backend
        fetch('process_login.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            
            // Log the raw response
            response.clone().text().then(text => {
                console.log('Raw response:', text);
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const contentType = response.headers.get('content-type');
            console.log('Content-Type:', contentType);
            
            if (!contentType || !contentType.includes('application/json')) {
                throw new TypeError("Expected JSON response but got " + contentType);
            }
            return response.json();
        })
        .then(data => {
            console.log('Parsed response data:', data);
            if (data.success) {
                // Store user data
                localStorage.setItem('currentUser', JSON.stringify({
                    username: username
                }));
                // Redirect using form submission
                redirectPost('generator.html');
                // Prevent any further code execution
                return;
            }
            // Only show alert if there's an error
            if (data.message) {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Detailed error:', {
                message: error.message,
                name: error.name,
                stack: error.stack
            });
            alert('An error occurred. Please try again later. ' + error.message);
        });
    });
}

// Handle logout
function logout() {
    localStorage.removeItem('currentUser');
    window.location.href = 'login.html';
} 