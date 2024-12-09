# Performance Test Simulation

# Objective

To test the performance between different languages by simulating a restaurant table reservation system entirely in memory.

# Result
Go: 2.48 seconds

NodeJS: 5.6 seconds

PHP8.4 with JIT: 8.39 php -d opcache.enable_cli=1 -d opcache.jit_buffer_size=100M -d opcache.jit=tracing  test.php 

PHP8.4: 67.93 seconds

Python3: 57.37 seconds


# Scenario Overview


This program simulates a restaurant reservation scenario to measure performance. It creates a set of 100 tables, each initially capable of seating 4 guests. When tables are combined, their total capacity changes based on a rule derived from the provided examples. For example:
- 1 table: 4 guests
- 2 tables combined: 6 guests total
- 3 tables combined: 8 guests total

The program runs multiple iterations (in this case, 100,000) of simulations where random reservations are requested and managed until all tables have been used at least once.

## Key Points

1. **Tables and Capacity:**  
   - 100 tables in total.  
   - Each table alone can hold up to 4 guests.  
   - Combining tables reduces overall capacity according to a formula:
     ```  
     combinedCapacity(n) = n * 4 - (n - 1) * 2
     ```
     This matches the examples given:
     - 1 table = 4 guests
     - 2 tables = 6 guests
     - 3 tables = 8 guests
     and so forth.

2. **Reservations:**
   - Guests can request between 1 and 15 seats.
   - If suitable tables cannot be found, the request is declined.
   - Once a reservation is made, those tables are considered occupied.

3. **Timing and Duration:**
   - Reservations can start at any 15-minute interval between 12:00 and 22:00.
   - A table remains occupied for 1 hour plus an additional 5 minutes per guest. For example, 4 guests occupy a table for 1 hour 20 minutes.
   - Preparation time after a party leaves is 15 minutes per table used.

4. **Data Storage (DTO):**
   - Each reservation is stored in a `ReservationDTO` that includes:
     - A 64-character hexadecimal ID.
     - A customer name (5-letter string) and phone number (9 digits).
     - The number of guests.
     - The start and end times of the reservation.
     - The list of tables allocated.

5. **Customer Requests and Modifications:**
   - Every 10th call, a customer tries to modify an existing reservation (cancel it and attempt to rebook).
   - Every 20th call, a customer cancels a reservation entirely, freeing the tables.
   - The simulation continues until all tables have been used at least once or a maximum number of calls is reached.

6. **Performance Testing:**
   - The simulation is designed to run 100,000 times (or another large number of iterations) to measure performance.
   - It does not rely on external libraries, only standard language features.
   - Customer names and phone numbers are generated once and reused across all iterations.

## Usage

The code can be adapted to various programming languages and environments (e.g., Node.js, PHP, Go, Python) as demonstrated. Simply run the code to generate and manage reservations, and to test how quickly the system can handle and exhaust all table combinations.

By analyzing the time it takes to complete these iterations, you can compare runtime performance, memory usage, and other metrics between different languages or system configurations.

## Conclusion

This simulation provides a controlled scenario of random reservation requests, modifications, cancellations, and resource allocation. It serves as a stress test for performance and efficiency across multiple runs, enabling direct comparisons between different runtime environments or implementations.
