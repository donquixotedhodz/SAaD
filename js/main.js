document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing...');
    
    // Load room types
    loadRoomTypes();
    
    // Set minimum dates for check-in and check-out
    const today = new Date().toISOString().split('T')[0];
    document.querySelector('input[name="check_in_date"]').min = today;
    document.querySelector('input[name="check_out_date"]').min = today;
    
    // Set default check-in time to current time rounded to nearest hour
    const now = new Date();
    const currentHour = now.getHours().toString().padStart(2, '0');
    const currentMinute = '00';
    document.querySelector('input[name="check_in_time"]').value = `${currentHour}:${currentMinute}`;
    
    // Store room types globally
    window.roomTypes = [];
    
    // Add event listeners for date and time changes
    document.querySelector('input[name="check_in_date"]').addEventListener('change', updateCheckOutDateTime);
    document.querySelector('input[name="check_in_time"]').addEventListener('change', updateCheckOutDateTime);
    document.querySelector('select[name="room_type"]').addEventListener('change', updateCheckOutDateTime);
});

async function loadRoomTypes() {
    console.log('Loading room types...');
    try {
        const response = await fetch('includes/get_room_types.php');
        console.log('Response received:', response);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const roomTypes = await response.json();
        console.log('Room types loaded:', roomTypes);
        
        if (!Array.isArray(roomTypes) || roomTypes.length === 0) {
            throw new Error('No room types found or invalid data received');
        }
        
        // Store room types globally
        window.roomTypes = roomTypes;
        
        // Clear loading spinner
        document.getElementById('roomsContainer').innerHTML = '';
        
        // Populate room type select
        const select = document.querySelector('select[name="room_type"]');
        select.innerHTML = '<option value="">Select Room Type</option>';
        roomTypes.forEach(type => {
            const option = document.createElement('option');
            option.value = type.id;
            option.textContent = `${type.name} - ₱${type.price.toLocaleString()}`;
            select.appendChild(option);
        });
        
        // Populate rooms container
        const roomsContainer = document.getElementById('roomsContainer');
        roomTypes.forEach(type => {
            const roomCard = createRoomCard(type);
            roomsContainer.appendChild(roomCard);
        });
    } catch (error) {
        console.error('Error loading room types:', error);
        document.getElementById('roomsContainer').innerHTML = `
            <div class="col-12 text-center">
                <div class="alert alert-danger">
                    Error loading rooms. Please try refreshing the page.
                    <br>
                    Details: ${error.message}
                </div>
            </div>
        `;
    }
}

function updateCheckOutDateTime() {
    const selectedOption = document.querySelector('select[name="room_type"]');
    if (!selectedOption.value) return;

    const roomType = window.roomTypes.find(r => r.id === parseInt(selectedOption.value));
    if (!roomType) return;

    const checkInDate = document.getElementById('check_in_date').value;
    const checkInTime = document.getElementById('check_in_time').value;
    
    if (checkInDate && checkInTime) {
        const checkIn = new Date(`${checkInDate}T${checkInTime}`);
        const checkOut = new Date(checkIn.getTime() + (roomType.duration_hours * 60 * 60 * 1000));
        
        document.getElementById('check_out_date').value = checkOut.toISOString().split('T')[0];
        document.getElementById('check_out_time').value = checkOut.toTimeString().slice(0, 5);
    }
}

function formatDuration(hours) {
    if (hours >= 24) {
        const days = hours / 24;
        return `${days} Day${days > 1 ? 's' : ''}`;
    }
    return `${hours} Hour${hours > 1 ? 's' : ''}`;
}

function createRoomCard(roomType) {
    console.log('Creating room card for:', roomType);
    const col = document.createElement('div');
    col.className = 'col-md-4 mb-4';
    
    col.innerHTML = `
        <div class="card room-card h-100">
            <img src="assets/rooms/${roomType.id}.jpg" class="card-img-top" alt="${roomType.name}" onerror="this.src='assets/rooms/default.jpg'">
            <div class="card-body">
                <h5 class="card-title">${roomType.name}</h5>
                <p class="card-text">${roomType.description}</p>
                <div class="room-details mb-3">
                    <p class="card-text mb-1">
                        <strong class="text-primary">₱${parseFloat(roomType.price).toLocaleString()}</strong>
                        <span class="text-muted">/ ${formatDuration(roomType.duration_hours)}</span>
                    </p>
                    <p class="card-text text-muted">
                        <small>
                            <i class="fas fa-user-friends"></i> Up to ${roomType.capacity} guests
                        </small>
                    </p>
                </div>
                <button class="btn btn-primary w-100" onclick="openBookingModal(${roomType.id}, '${roomType.name}')">
                    Book Now
                </button>
            </div>
        </div>
    `;
    
    return col;
}

function openBookingModal(roomTypeId, roomTypeName) {
    console.log('Opening booking modal for room:', roomTypeId, roomTypeName);
    const select = document.querySelector('select[name="room_type"]');
    select.value = roomTypeId;
    
    // Trigger the change event to update check-out date/time
    select.dispatchEvent(new Event('change'));
    
    const modal = new bootstrap.Modal(document.getElementById('bookingModal'));
    modal.show();
}

function submitBookingForm() {
    const form = document.getElementById('bookingForm');
    const formData = new FormData(form);
    
    fetch('includes/process_booking.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        const bookingModal = bootstrap.Modal.getInstance(document.getElementById('bookingModal'));
        bookingModal.hide();
        
        if (result.success) {
            showBookingConfirmation(result.booking_id, result.check_in, result.check_out);
        } else {
            alert(result.message || 'An error occurred during booking. Please try again.');
        }
    })
    .catch(error => {
        console.error('Booking error:', error);
        alert('An error occurred during booking. Please try again.');
    });
}

function showBookingConfirmation(bookingId, checkIn, checkOut) {
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.id = 'bookingConfirmationModal';
    
    modal.innerHTML = `
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Booking Confirmed!</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <p>Your booking has been confirmed.</p>
                    <p><strong>Booking ID:</strong> ${bookingId}</p>
                    <p><strong>Check-in:</strong> ${new Date(checkIn).toLocaleString()}</p>
                    <p><strong>Check-out:</strong> ${new Date(checkOut).toLocaleString()}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    const modalInstance = new bootstrap.Modal(modal);
    modalInstance.show();
    
    modal.addEventListener('hidden.bs.modal', () => modal.remove());
}
