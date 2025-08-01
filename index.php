<?php
header('Content-Type: application/json');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Event Management API</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .endpoint { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
        .method { font-weight: bold; color: #007cba; }
        .url { font-family: monospace; background: #f5f5f5; padding: 5px; }
        .description { margin: 10px 0; }
        .example { background: #f9f9f9; padding: 10px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>Event Management Platform API</h1>
    
    <h2>Authentication Endpoints</h2>
    <div class="endpoint">
        <div class="method">POST</div>
        <div class="url">/auth/register.php</div>
        <div class="description">Register a new user</div>
        <div class="example">
            Body: {"name": "John Doe", "email": "john@example.com", "password": "password123", "role": "organizer"}
        </div>
    </div>
    
    <div class="endpoint">
        <div class="method">POST</div>
        <div class="url">/auth/login.php</div>
        <div class="description">Login user and start session</div>
        <div class="example">
            Body: {"email": "john@example.com", "password": "password123"}
        </div>
    </div>
    
    <div class="endpoint">
        <div class="method">POST</div>
        <div class="url">/auth/logout.php</div>
        <div class="description">Logout user and end session</div>
    </div>
    
    <h2>Event Endpoints</h2>
    <div class="endpoint">
        <div class="method">POST</div>
        <div class="url">/events/create_event.php</div>
        <div class="description">Create new event (Organizer only)</div>
        <div class="example">
            Body: {"title": "Tech Conference", "description": "Annual tech event", "date": "2025-12-01 10:00:00", "location": "Mumbai", "ticket_price": 500}
        </div>
    </div>
    
    <div class="endpoint">
        <div class="method">GET</div>
        <div class="url">/events/list_events.php</div>
        <div class="description">List all events (Public)</div>
    </div>
    
    <div class="endpoint">
        <div class="method">GET</div>
        <div class="url">/events/event_details.php?id=1</div>
        <div class="description">Get event details by ID</div>
    </div>
    
    <h2>Ticket Endpoints</h2>
    <div class="endpoint">
        <div class="method">POST</div>
        <div class="url">/tickets/book_ticket.php</div>
        <div class="description">Book tickets for an event (Authenticated users)</div>
        <div class="example">
            Body: {"event_id": 1, "quantity": 2}
        </div>
    </div>
    
    <div class="endpoint">
        <div class="method">GET</div>
        <div class="url">/tickets/my_tickets.php</div>
        <div class="description">View user's booked tickets (Authenticated users)</div>
    </div>
    
    <h2>Sponsor Endpoints</h2>
    <div class="endpoint">
        <div class="method">GET</div>
        <div class="url">/sponsors/match_events.php</div>
        <div class="description">Get available events for sponsorship (Sponsor only)</div>
    </div>
    
    <h2>Sponsorship Endpoints</h2>
    <div class="endpoint">
        <div class="method">POST</div>
        <div class="url">/sponsorships/send_proposal.php</div>
        <div class="description">Send sponsorship proposal (Organizer only)</div>
        <div class="example">
            Body: {"event_id": 1, "sponsor_id": 2, "proposal_text": "We would like to sponsor your event..."}
        </div>
    </div>
    
    <div class="endpoint">
        <div class="method">POST</div>
        <div class="url">/sponsorships/respond.php</div>
        <div class="description">Respond to sponsorship proposal (Sponsor only)</div>
        <div class="example">
            Body: {"proposal_id": 1, "response": "accepted"}
        </div>
    </div>
    
    <h2>Admin Endpoints</h2>
    <div class="endpoint">
        <div class="method">GET</div>
        <div class="url">/admin/users.php</div>
        <div class="description">View all users (Admin only)</div>
    </div>
    
    <div class="endpoint">
        <div class="method">GET</div>
        <div class="url">/admin/events.php</div>
        <div class="description">View all events with statistics (Admin only)</div>
    </div>
    
    <h2>User Roles</h2>
    <ul>
        <li><strong>organizer</strong>: Can create events and send sponsorship proposals</li>
        <li><strong>sponsor</strong>: Can view available events and respond to proposals</li>
        <li><strong>ticket_buyer</strong>: Can book tickets and view their bookings</li>
        <li><strong>admin</strong>: Can view all users and events with statistics</li>
    </ul>
    
    <h2>Testing Instructions</h2>
    <ol>
        <li>Import the database schema from <code>database/schema.sql</code></li>
        <li>Update database credentials in <code>config/db.php</code></li>
        <li>Use Postman or curl to test the endpoints</li>
        <li>Default admin login: admin@example.com / password</li>
    </ol>
</body>
</html>

<?php
