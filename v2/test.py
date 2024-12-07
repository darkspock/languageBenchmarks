import random
import secrets

# ==============================================
# Configuration and Data Structures (Constants)
# ==============================================

NUM_TABLES = 100
MAX_CAPACITY_PER_TABLE = 4
NUM_CUSTOMERS = 200
START_TIME = 12 * 60  # 12:00 in minutes from start of day
END_TIME = 22 * 60    # 22:00 in minutes
INTERVAL = 15          # 15-minute intervals
TOTAL_ITERATIONS = 100000

# According to the examples:
# 1 table = 4 seats
# 2 tables combined = 6 seats
# 3 tables combined = 8 seats
# General formula: combinedCapacity(n) = n*MAX_CAPACITY_PER_TABLE - (n-1)*2
def combined_capacity(num_tables):
    return num_tables * MAX_CAPACITY_PER_TABLE - (num_tables - 1) * 2

time_slots = list(range(START_TIME, END_TIME + 1, INTERVAL))

# ==============================================
# Class for Reservation DTO
# ==============================================
class ReservationDTO:
    def __init__(self, id, name, phone, guests, start_time, end_time, tables):
        self.id = id
        self.name = name
        self.phone = phone
        self.guests = guests
        self.startTime = start_time
        self.endTime = end_time
        self.tables = tables

# ==============================================
# Random Data Generation Functions
# ==============================================

def generate_random_name():
    # 5-letter name a-z
    return ''.join(chr(97 + random.randint(0, 25)) for _ in range(5))

def generate_random_phone():
    # 9-digit phone number
    return ''.join(str(random.randint(0,9)) for _ in range(9))

def generate_id_64_hex():
    # 64 hex chars = 32 bytes hex encoded
    return secrets.token_hex(32)

def generate_customers(num):
    return [{"name": generate_random_name(), "phone": generate_random_phone()} for _ in range(num)]

customers = generate_customers(NUM_CUSTOMERS)

# ==============================================
# Helper Functions
# ==============================================

def check_all_tables_used_once(tables):
    return all(t["timesUsed"] > 0 for t in tables)

def find_tables_for_guests(guest_count, tables):
    for n in range(1, NUM_TABLES + 1):
        cap = combined_capacity(n)
        if cap >= guest_count:
            free_tables = [i for i, t in enumerate(tables) if not t["occupied"]]
            if len(free_tables) >= n:
                return free_tables[:n]
    return None

def occupy_tables(tables_indices, guests, start_time, tables, reservations, customers):
    occupation_time = 60 + guests * 5
    end_time = start_time + occupation_time

    for idx in tables_indices:
        tables[idx]["occupied"] = True
        tables[idx]["timesUsed"] += 1

    random_customer = random.choice(customers)
    res = ReservationDTO(
        id=generate_id_64_hex(),
        name=random_customer["name"],
        phone=random_customer["phone"],
        guests=guests,
        start_time=start_time,
        end_time=end_time,
        tables=list(tables_indices)
    )
    reservations.append(res)
    return res

def cancel_reservation(res_id, reservations, tables):
    for i, r in enumerate(reservations):
        if r.id == res_id:
            for ti in r.tables:
                tables[ti]["occupied"] = False
            reservations[i] = reservations[-1]
            reservations.pop()
            return True
    return False

def modify_reservation(res_id, new_guests, new_start_time, reservations, tables, customers, time_slots):
    if not cancel_reservation(res_id, reservations, tables):
        return None
    t = find_tables_for_guests(new_guests, tables)
    if t is None:
        return None
    return occupy_tables(t, new_guests, new_start_time, tables, reservations, customers)

def run_simulation(customers, time_slots):
    tables = [{"id": i, "occupied": False, "timesUsed": 0} for i in range(NUM_TABLES)]
    reservations = []
    call_count = 0
    all_tables_used_once = False

    while not all_tables_used_once:
        call_count += 1
        guests = 1 + random.randint(0,14)  # 1 to 15
        start_time = random.choice(time_slots)

        if call_count % 20 == 0 and reservations:
            # every 20 calls, cancel a reservation
            rnd_res = random.choice(reservations).id
            cancel_reservation(rnd_res, reservations, tables)
        elif call_count % 10 == 0 and reservations:
            # every 10 calls, modify a reservation
            rnd_res = random.choice(reservations).id
            new_guests = 1 + random.randint(0,14)
            new_start_time = random.choice(time_slots)
            modify_reservation(rnd_res, new_guests, new_start_time, reservations, tables, customers, time_slots)
        else:
            # normal reservation attempt
            t = find_tables_for_guests(guests, tables)
            if t is not None:
                occupy_tables(t, guests, start_time, tables, reservations, customers)

        all_tables_used_once = check_all_tables_used_once(tables)
        if call_count > 100000:
            break

    return len(reservations), call_count, all_tables_used_once

# ==============================================
# Run the simulation 100,000 times
# ==============================================

for i in range(TOTAL_ITERATIONS):
    run_simulation(customers, time_slots)

print("All iterations completed.")
