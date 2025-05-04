<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Richard's Hotel - Your Perfect Stay</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.all.min.js"></script>
    <!-- Google AI SDK -->
    <script src="https://ai.google.com/js/api.js"></script>
    <!-- Custom CSS -->
    <link href="css/style.css" rel="stylesheet">
<link rel="stylesheet" href="style.css">
    <script>
        const API_KEY = 'AIzaSyAJIqhxOGvkNfpcicGPhxYe85bF5t1a-7c';
        gapi.load('client', initGoogleAI);

        async function initGoogleAI() {
            try {
                await gapi.client.init({
                    apiKey: API_KEY,
                    discoveryDocs: ['https://generativelanguage.googleapis.com/$discovery/rest?version=v1'],
                });
                console.log('Google AI initialized successfully');
            } catch (error) {
                console.error('Error initializing Google AI:', error);
            }
        }
    </script>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <!-- Update logo image - place a logo (40x40px recommended) in images folder -->
                <img src="images/logo1.png" alt="Richard's Hotel Logo" class="navbar-logo me-2">
                Richard's Hotel
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#rooms">Rooms</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="customer/login.php">
                            <i class="fas fa-user"></i> Guest Login
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <header id="home" class="hero">
        <div class="hero-overlay"></div>
        <div class="container">
            <div class="row">
                <div class="col-12 text-center text-white">
                    <h1 class="display-3 fw-bold">Welcome to Richard's Hotel</h1>
                    <p class="lead mb-4">Experienced one of a lifetime</p>
                    
                </div>
            </div>
        </div>
    </header>

    <!-- Rooms Section -->
    <section id="rooms" class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Our Rooms</h2>
            <div class="row" id="roomsContainer">
                <!-- Rooms will be loaded dynamically -->
            </div>
        </div>
    </section>

    <!-- Booking Modal -->
    <div class="modal fade" id="bookingModal" tabindex="-1" aria-labelledby="bookingModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bookingModalLabel">Book a Room</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="bookingForm" action="includes/process_booking.php" method="POST">
                        <input type="hidden" id="room_id" name="room_id">
                        <div class="row mb-3">
                            <div class="col-mb-3">
                                <div class="form-group">
                                    <select name="room_type" id="room_type" class="form-select" required>
                                    </select>
                                </div>  
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" id="phone" name="phone" required>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="check_in_date" class="form-label">Check-in Date</label>
                                <input type="date" class="form-control" id="check_in_date" name="check_in_date" required>
                            </div>
                            <div class="col-md-6">
                                <label for="check_in_time" class="form-label">Check-in Time</label>
                                <input type="time" class="form-control" id="check_in_time" name="check_in_time" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="check_out_date" class="form-label">Check-out Date</label>
                                <input type="date" class="form-control" id="check_out_date" name="check_out_date" readonly>
                            </div>
                            <div class="col-md-6">
                                <label for="check_out_time" class="form-label">Check-out Time</label>
                                <input type="time" class="form-control" id="check_out_time" name="check_out_time" readonly>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Total Amount</label>
                            <div id="total_display" class="alert alert-info">
                                Total Amount: ₱0
                            </div>
                            <input type="hidden" id="total_amount" name="total_amount" value="0">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Book Now</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Chatbot Button -->
    <div class="chat-button" id="chatButton">
        <i class="fas fa-comments fa-lg"></i>
    </div>

    <!-- Chatbot Modal -->
    <div class="modal fade chat-modal" id="chatModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="chat-header">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-robot me-2"></i>
                        <h5 class="modal-title mb-0">Hotel Assistant</h5>
                        <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"></button>
                    </div>
                </div>
                <div class="chat-messages" id="chatMessages">
                    <div class="message bot-message">
                        Hello! Welcome to Richard's Hotel. How can I assist you today?
                    </div>
                </div>
                <div class="chat-input">
                    <form id="chatForm" class="d-flex gap-2">
                        <input type="text" class="form-control" id="messageInput" placeholder="Type your message...">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Contact Us</h5>
                    <p>Email: test@gmail.com<br>
                    Phone: +639 95 871 4112</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <h5>Follow Us</h5>
                    <a href="#" class="text-white me-2"><i class="fab fa-facebook"></i></a>
                    <a href="#" class="text-white me-2"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-white"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
    
    <!-- Custom JS -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        let roomTypesData = [];
        const roomsContainer = document.getElementById('roomsContainer');
        const roomTypeSelect = document.getElementById('room_type');
        const bookingForm = document.getElementById('bookingForm');

        // Load room types and initialize the page
        async function initializePage() {
            try {
                const response = await fetch('includes/get_room_types.php');
                if (!response.ok) throw new Error('Failed to load room types');
                
                const data = await response.json();
                roomTypesData = data;
                
                populateRoomTypeSelect(data);
                displayRoomCards(data);
            } catch (error) {
                console.error('Initialization error:', error);
                roomsContainer.innerHTML = `
                    <div class="col-12 text-center">
                        <div class="alert alert-danger">
                            Failed to load rooms. Please refresh the page.
                        </div>
                    </div>`;
            }
        }

        // Replace the existing populateRoomTypeSelect function
        function populateRoomTypeSelect(data) {
            roomTypeSelect.innerHTML = '<option value="">Select a room type</option>';
            data.forEach(room => {
                roomTypeSelect.innerHTML += `
                    <option value="${room.id}" 
                            data-duration="${room.duration_hours}"
                            data-rate="${room.price}">
                        ${room.name} - ₱${room.price.toLocaleString()}
                    </option>`;
            });
        }

        // Display room cards
        function displayRoomCards(data) {
    roomsContainer.innerHTML = data.map(room => `
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card h-100 room-card">
                <img src="images/room-${room.id}.jpg" 
                     class="card-img-top" 
                     alt="${room.name}" 
                     onerror="this.src='images/default-room.jpg'"
                     loading="lazy">
                <div class="card-body">
                    <h5 class="card-title">${room.name}</h5>
                    <p class="card-text">${room.description || 'No description available'}</p>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-peso-sign"></i> ₱${room.price.toLocaleString()}</li>
                        <li><i class="fas fa-clock"></i> ${room.duration_hours}h</li>
                        <li><i class="fas fa-users"></i> ${room.capacity} pax</li>
                    </ul>
                    <button class="btn btn-primary w-100" 
                            onclick="openBookingModal(${room.id})">
                        Book Now
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

        // Open booking modal
        window.openBookingModal = async function(roomTypeId) {
            try {
                // Get available room for this room type
                const response = await fetch('includes/get_available_room.php?room_type_id=' + roomTypeId);
                if (!response.ok) throw new Error('Failed to get available room');
                
                const data = await response.json();
                if (!data.success) throw new Error(data.message || 'No rooms available');

                const now = new Date();
                now.setMinutes(0, 0, 0);

                roomTypeSelect.value = roomTypeId;
                document.getElementById('room_id').value = data.room_id;
                document.getElementById('check_in_time').value = now.toTimeString().slice(0, 5);
                
                updateCheckoutTime();
                new bootstrap.Modal(document.getElementById('bookingModal')).show();
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'No Rooms Available',
                    text: error.message,
                    confirmButtonColor: '#dc3545'
                });
            }
        };

        // Update the updateCheckoutTime function to calculate total amount
        function updateCheckoutTime() {
            const selectedRoomId = parseInt(roomTypeSelect.value);
            if (!selectedRoomId) return;

            const selectedRoom = roomTypesData.find(room => room.id === selectedRoomId);
            if (!selectedRoom) return;

            const checkInDate = document.getElementById('check_in_date').value;
            const checkInTime = document.getElementById('check_in_time').value;
            
            if (checkInDate && checkInTime) {
                const checkIn = new Date(`${checkInDate}T${checkInTime}`);
                const checkOut = new Date(checkIn.getTime() + (selectedRoom.duration_hours * 60 * 60 * 1000));
                
                document.getElementById('check_out_date').value = checkOut.toISOString().split('T')[0];
                document.getElementById('check_out_time').value = checkOut.toTimeString().slice(0, 5);

                // Calculate and set total amount
                const hours = selectedRoom.duration_hours;
                const rate = selectedRoom.price;
                const total = rate;
                
                // Add hidden input for total amount if it doesn't exist
                let totalInput = document.getElementById('total_amount');
                if (!totalInput) {
                    totalInput = document.createElement('input');
                    totalInput.type = 'hidden';
                    totalInput.id = 'total_amount';
                    totalInput.name = 'total_amount';
                    document.getElementById('bookingForm').appendChild(totalInput);
                }
                totalInput.value = total;

                // Display total amount to user (optional)
                let totalDisplay = document.getElementById('total_display');
                if (!totalDisplay) {
                    totalDisplay = document.createElement('div');
                    totalDisplay.id = 'total_display';
                    totalDisplay.className = 'alert alert-info mt-3';
                    document.getElementById('bookingForm').appendChild(totalDisplay);
                }
                totalDisplay.innerHTML = `Total Amount: ₱${total.toLocaleString()}`;
            }
        }

        // Show booking confirmation
        function showBookingConfirmation(bookingId, checkIn, checkOut) {
            Swal.fire({
                icon: 'success',
                title: 'Booking Confirmed!',
                html: `
                    <div class="text-start">
                        <p>Booking ID: ${bookingId}</p>
                        <p>Check-in: ${new Date(checkIn).toLocaleString()}</p>
                        <p>Check-out: ${new Date(checkOut).toLocaleString()}</p>
                    </div>
                `,
                confirmButtonText: 'OK'
            });
        }

        // Form submission handler
        bookingForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            try {
                // Show loading state
                const submitButton = this.querySelector('button[type="submit"]');
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';

                // Validate form data
                const formData = new FormData(this);
                const checkIn = new Date(`${formData.get('check_in_date')}T${formData.get('check_in_time')}`);
                const now = new Date();

                if (checkIn < now) {
                    throw new Error('Check-in time cannot be in the past');
                }

                // First check room availability
                const availabilityCheck = await fetch('includes/check_room_availability.php', {
                    method: 'POST',
                    body: formData
                });

                const availabilityData = await availabilityCheck.json();
                if (!availabilityData.available) {
                    throw new Error(availabilityData.message || 'Room is not available for selected dates');
                }

                // Process booking
                const response = await fetch(this.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.success) {
                    // Show success message
                    showBookingConfirmation(data.booking_id, data.check_in, data.check_out);
                    
                    // Close modal and reset form
                    const modal = bootstrap.Modal.getInstance(document.getElementById('bookingModal'));
                    modal.hide();
                    this.reset();
                    
                    // Refresh room display
                    await initializePage();
                } else {
                    throw new Error(data.message || 'Booking failed. Please try again.');
                }
            } catch (error) {
                console.error('Booking error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Booking Failed',
                    text: error.message || 'Failed to process booking. Please try again.',
                    confirmButtonColor: '#dc3545'
                });
            } finally {
                // Reset submit button
                const submitButton = this.querySelector('button[type="submit"]');
                submitButton.disabled = false;
                submitButton.innerHTML = 'Book Now';
            }
        });

        // Event listeners
        roomTypeSelect.addEventListener('change', updateCheckoutTime);
        ['check_in_date', 'check_in_time'].forEach(id => {
            document.getElementById(id).addEventListener('change', updateCheckoutTime);
        });

        // Set minimum date for check-in
        document.getElementById('check_in_date').min = new Date().toISOString().split('T')[0];

        // Initialize the page
        initializePage();

        // Hotel context for the AI
        const hotelContext = `You are the AI assistant for Richard's Hotel. Here are the key details about the hotel:
- Room types: Standard ($100/night), Deluxe ($150/night), and Suite ($250/night)
- Check-in time: 3:00 PM, Check-out time: 11:00 AM
- Amenities: Free WiFi, parking (self: $15/day, valet: $25/day), restaurant
- Restaurant hours: Breakfast (6:30 AM - 10:30 AM), Lunch (12:00 PM - 3:00 PM), Dinner (6:00 PM - 10:30 PM)
- Location: 123 Hotel Street, Cityville
- Contact: +1-234-567-8900, info@richardshotel.com
- Front desk: Available 24/7

Please provide helpful and friendly responses about these topics. If asked about something not covered in this context, provide a general response and suggest contacting the front desk for more specific information.`;

        // Chatbot functionality
        const chatButton = document.getElementById('chatButton');
        const chatModal = new bootstrap.Modal(document.getElementById('chatModal'));
        const chatMessages = document.getElementById('chatMessages');
        const chatForm = document.getElementById('chatForm');
        const messageInput = document.getElementById('messageInput');

        // Simple responses for common queries
        const quickResponses = {
            'hello': 'Hello! Welcome to Richard\'s Hotel. How can I assist you today?',
    'hi': 'Hi there! How can I help you with your stay at Richard\'s Hotel?',
    'rooms': 'We offer three types of rooms:\n- Standard Room: $100/night\n- Deluxe Room: $150/night\n- Suite: $250/night\nWould you like to know more about any specific room?',
    'booking': 'To make a booking, you can click the "Book Now" button at the top of our page. Would you like me to guide you through the process?',
    'check in': 'Check-in time is at 3:00 PM. Early check-in may be available upon request.',
    'check out': 'Check-out time is at 11:00 AM. Late check-out may be available upon request.',
    'wifi': 'Yes, we provide complimentary high-speed WiFi access throughout the hotel.',
    'parking': 'We offer both self-parking ($15/day) and valet parking ($25/day).',
    'restaurant': 'Our restaurant serves:\n- Breakfast: 6:30 AM - 10:30 AM\n- Lunch: 12:00 PM - 3:00 PM\n- Dinner: 6:00 PM - 10:30 PM',
    'location': 'We are located at 123 Hotel Street, Cityville.',
    'contact': 'You can reach us at:\n- Phone: +1-234-567-8900\n- Email: info@richardshotel.com\n- Front desk is available 24/7',
    'amenities': 'We offer:\n- Free high-speed WiFi\n- Restaurant\n- Parking\n- 24/7 front desk\n- Room service\nNeed more details about any of these?',
    'price': 'Our room rates are:\n- Standard: $100/night\n- Deluxe: $150/night\n- Suite: $250/night\nThese rates may vary based on season and availability.',
    'default': 'I\'m here to help you with information about our hotel, bookings, and services. Feel free to ask about our rooms, amenities, or services!',
    'room service': 'Yes, we offer 24/7 room service. You can order from the in-room menu or call the front desk for assistance.',
    'pool': 'Yes, we have an indoor heated swimming pool open daily from 7:00 AM to 10:00 PM.',
    'gym': 'Our fitness center is open 24/7 and includes cardio machines, weights, and yoga mats.',
    'spa': 'Our spa offers massages, facials, and wellness treatments. Open daily from 10:00 AM to 8:00 PM. Would you like to schedule an appointment?',
    'events': 'We offer event spaces for meetings, conferences, and weddings. Would you like details on availability and pricing?',
    'cancellation': 'You can cancel free of charge up to 24 hours before check-in. After that, a one-night fee applies.',
    'pet policy': 'Pets are welcome in select rooms for a $30/night fee. Please let us know in advance if you’re bringing a pet.',
    'airport shuttle': 'We provide a complimentary airport shuttle service every hour from 6:00 AM to 10:00 PM. Would you like to reserve a spot?',
    'laundry': 'We offer same-day laundry and dry cleaning services. Just leave items in the laundry bag and contact the front desk.',
    'early check in': 'Early check-in is subject to availability. Please check with us on your arrival day.',
    'late check out': 'Late check-out may be available upon request. A fee may apply depending on the time.',
    'hello': 'Hello! Welcome to Richard\'s Hotel. How can I assist you today?',
    'hi': 'Hi there! How can I help you with your stay at Richard\'s Hotel?',
    'what kinds of rooms do you have?': 'We offer three types of rooms:\n- Standard Room: $100/night\n- Deluxe Room: $150/night\n- Suite: $250/night\nWould you like to know more about any specific room?',
    'how do I book a room?': 'To make a booking, you can click the "Book Now" button at the top of our page. Would you like me to guide you through the process?',
    'what time is check-in?': 'Check-in time is at 3:00 PM. Early check-in may be available upon request.',
    'what time is check-out?': 'Check-out time is at 11:00 AM. Late check-out may be available upon request.',
    'do you have wifi?': 'Yes, we provide complimentary high-speed WiFi access throughout the hotel.',
    'do you have parking?': 'We offer both self-parking ($15/day) and valet parking ($25/day).',
    'what are the restaurant hours?': 'Our restaurant serves:\n- Breakfast: 6:30 AM - 10:30 AM\n- Lunch: 12:00 PM - 3:00 PM\n- Dinner: 6:00 PM - 10:30 PM',
    'where is the hotel located?': 'We are located at 123 Hotel Street, Cityville.',
    'how can I contact you?': 'You can reach us at:\n- Phone: +1-234-567-8900\n- Email: info@richardshotel.com\n- Front desk is available 24/7',
    'what amenities do you offer?': 'We offer:\n- Free high-speed WiFi\n- Restaurant\n- Parking\n- 24/7 front desk\n- Room service\nNeed more details about any of these?',
    'how much are the rooms?': 'Our room rates are:\n- Standard: $100/night\n- Deluxe: $150/night\n- Suite: $250/night\nThese rates may vary based on season and availability.',
    'do you have room service?': 'Yes, we offer 24/7 room service. You can order from the in-room menu or call the front desk for assistance.',
    'is there a pool?': 'Yes, we have an indoor heated swimming pool open daily from 7:00 AM to 10:00 PM.',
    'is there a gym?': 'Our fitness center is open 24/7 and includes cardio machines, weights, and yoga mats.',
    'do you have a spa?': 'Our spa offers massages, facials, and wellness treatments. Open daily from 10:00 AM to 8:00 PM. Would you like to schedule an appointment?',
    'can I host an event at your hotel?': 'We offer event spaces for meetings, conferences, and weddings. Would you like details on availability and pricing?',
    'what is your cancellation policy?': 'You can cancel free of charge up to 24 hours before check-in. After that, a one-night fee applies.',
    'can I bring my pet?': 'Pets are welcome in select rooms for a $30/night fee. Please let us know in advance if you’re bringing a pet.',
    'do you offer an airport shuttle?': 'We provide a complimentary airport shuttle service every hour from 6:00 AM to 10:00 PM. Would you like to reserve a spot?',
    'do you have laundry service?': 'We offer same-day laundry and dry cleaning services. Just leave items in the laundry bag and contact the front desk.',
    'can I check in early?': 'Early check-in is subject to availability. Please check with us on your arrival day.',
    'can I check out late?': 'Late check-out may be available upon request. A fee may apply depending on the time.',
    'default': 'I\'m here to help you with information about our hotel, bookings, and services. Just ask me anything, like "how do I book a room?" or "do you have a gym?"'
    
        };

        chatButton.addEventListener('click', () => {
            chatModal.show();
        });

        function findBestResponse(message) {
            message = message.toLowerCase();
            
            // Check for exact matches first
            for (let key in quickResponses) {
                if (message.includes(key)) {
                    return quickResponses[key];
                }
            }
            
            // If no match found, return default response
            return quickResponses['default'];
        }

        chatForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const message = messageInput.value.trim();
            if (!message) return;

            // Add user message
            addMessage(message, 'user');
            messageInput.value = '';

            // Show typing indicator
            const typingIndicator = document.createElement('div');
            typingIndicator.className = 'message bot-message typing';
            typingIndicator.innerHTML = '<div class="typing-dots"><span>.</span><span>.</span><span>.</span></div>';
            chatMessages.appendChild(typingIndicator);
            chatMessages.scrollTop = chatMessages.scrollHeight;

            // Simulate processing time
            setTimeout(() => {
                // Remove typing indicator
                typingIndicator.remove();

                // Get and add bot response
                const response = findBestResponse(message);
                addMessage(response, 'bot');
            }, 1000);
        });

        function addMessage(text, type) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${type}-message`;
            messageDiv.textContent = text;
            chatMessages.appendChild(messageDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    });
    </script>
</body>
</html>