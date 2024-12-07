// ==============================================
// Configuration and Data Structures
// ==============================================

const NUM_TABLES = 100;
const MAX_CAPACITY_PER_TABLE = 4;
const NUM_CUSTOMERS = 200;
const START_TIME = 12 * 60; // 12:00 in minutes from start of the day
const END_TIME = 22 * 60;   // 22:00 in minutes
const INTERVAL = 15;        // 15-minute intervals
const TOTAL_ITERATIONS = 100000; // Repeat the entire simulation 100,000 times

// According to examples provided:
// 1 table = 4 seats
// 2 tables combined = 6 seats
// 3 tables combined = 8 seats
// The formula from the examples: combinedCapacity(n) = n*MAX_CAPACITY_PER_TABLE - (n-1)*2
function combinedCapacity(numTables) {
    return numTables * MAX_CAPACITY_PER_TABLE - (numTables - 1) * 2;
}

// Generate time slots
let timeSlots = [];
for (let t = START_TIME; t <= END_TIME; t += INTERVAL) {
    timeSlots.push(t);
}

// ==============================================
// Class for Reservation DTO
// ==============================================
class ReservationDTO {
    constructor(id, name, phone, guests, startTime, endTime, tables) {
        this.id = id;
        this.name = name;
        this.phone = phone;
        this.guests = guests;
        this.startTime = startTime;
        this.endTime = endTime;
        this.tables = tables;
    }
}

// ==============================================
// Random Data Generation Functions
// ==============================================

function generateRandomName() {
    let name = '';
    for (let i = 0; i < 5; i++) {
        const charCode = 97 + Math.floor(Math.random() * 26);
        name += String.fromCharCode(charCode);
    }
    return name;
}

function generateRandomPhone() {
    let phone = '';
    for (let i = 0; i < 9; i++) {
        phone += Math.floor(Math.random() * 10).toString();
    }
    return phone;
}

function generateId64Hex() {
    const hexChars = '0123456789abcdef';
    let id = '';
    for (let i = 0; i < 64; i++) {
        id += hexChars[Math.floor(Math.random() * 16)];
    }
    return id;
}

function generateCustomers(num) {
    const customers = [];
    for (let i = 0; i < num; i++) {
        customers.push({
            name: generateRandomName(),
            phone: generateRandomPhone(),
        });
    }
    return customers;
}

const customers = generateCustomers(NUM_CUSTOMERS);

// ==============================================
// Helper Functions
// ==============================================

function checkAllTablesUsedOnce(tables) {
    return tables.every(t => t.timesUsed > 0);
}

function findTablesForGuests(guestCount, tables) {
    for (let n = 1; n <= NUM_TABLES; n++) {
        const cap = combinedCapacity(n);
        if (cap >= guestCount) {
            let freeTablesIndices = [];
            for (let i = 0; i < tables.length && freeTablesIndices.length < n; i++) {
                if (!tables[i].occupied) {
                    freeTablesIndices.push(i);
                }
            }
            if (freeTablesIndices.length === n) {
                return freeTablesIndices;
            }
        }
    }
    return null;
}

function occupyTables(tablesIndices, guests, startTime, tables, reservations, customers) {
    const occupationTime = 60 + guests * 5; // 1h + 5m per guest
    const endTime = startTime + occupationTime;

    tablesIndices.forEach(i => {
        tables[i].occupied = true;
        tables[i].timesUsed += 1;
    });

    const randomCustomer = customers[Math.floor(Math.random() * customers.length)];

    const reservation = new ReservationDTO(
        generateId64Hex(),
        randomCustomer.name,
        randomCustomer.phone,
        guests,
        startTime,
        endTime,
        tablesIndices.slice()
    );

    reservations.push(reservation);
    return reservation;
}

function cancelReservation(resId, reservations, tables) {
    const index = reservations.findIndex(r => r.id === resId);
    if (index === -1) return false;
    const res = reservations[index];
    res.tables.forEach(i => {
        tables[i].occupied = false;
    });
    reservations.splice(index, 1);
    return true;
}

function modifyReservation(resId, newGuests, newStartTime, reservations, tables, customers, timeSlots) {
    const canceled = cancelReservation(resId, reservations, tables);
    if (!canceled) return null;
    const t = findTablesForGuests(newGuests, tables);
    if (!t) return null;
    return occupyTables(t, newGuests, newStartTime, tables, reservations, customers);
}

function runSimulation(customers, timeSlots) {
    const tables = new Array(NUM_TABLES).fill(null).map((_, i) => {
        return {
            id: i,
            occupied: false,
            timesUsed: 0
        };
    });

    const reservations = [];
    let callCount = 0;
    let allTablesUsedOnce = false;

    while (!allTablesUsedOnce) {
        callCount++;
        const guests = 1 + Math.floor(Math.random() * 15);
        const startTime = timeSlots[Math.floor(Math.random() * timeSlots.length)];

        if (callCount % 20 === 0 && reservations.length > 0) {
            // Every 20 calls, cancel a reservation
            const rndRes = reservations[Math.floor(Math.random() * reservations.length)].id;
            cancelReservation(rndRes, reservations, tables);
        } else if (callCount % 10 === 0 && reservations.length > 0) {
            // Every 10 calls (not 20), modify a reservation
            const rndRes = reservations[Math.floor(Math.random() * reservations.length)].id;
            const newGuests = 1 + Math.floor(Math.random() * 15);
            const newStartTime = timeSlots[Math.floor(Math.random() * timeSlots.length)];
            modifyReservation(rndRes, newGuests, newStartTime, reservations, tables, customers, timeSlots);
        } else {
            // Normal reservation attempt
            const t = findTablesForGuests(guests, tables);
            if (t) {
                occupyTables(t, guests, startTime, tables, reservations, customers);
            }
        }

        allTablesUsedOnce = checkAllTablesUsedOnce(tables);

        // Safeguard
        if (callCount > 100000) {
            break;
        }
    }

    return {
        totalReservations: reservations.length,
        totalCalls: callCount,
        allUsed: allTablesUsedOnce
    };
}

// ==============================================
// Run the simulation 100,000 times
// ==============================================
for (let i = 0; i < TOTAL_ITERATIONS; i++) {
    runSimulation(customers, timeSlots);
}

console.log("All iterations completed.");
