import 'dart:math';
import 'dart:convert';
import 'dart:typed_data';
import 'package:crypto/crypto.dart'; // Not strictly necessary if we implement hex generation manually
// If external packages are not allowed, we must generate hex manually
// Note: If you cannot use `package:crypto`, you'll have to implement a random hex generator using Dart's Random class.

// ==============================================
// Configuration and Data Structures (Constants)
// ==============================================

const NUM_TABLES = 100;
const MAX_CAPACITY_PER_TABLE = 4;
const NUM_CUSTOMERS = 200;
const START_TIME = 12 * 60; // 12:00 in minutes from start of day
const END_TIME = 22 * 60;   // 22:00 in minutes
const INTERVAL = 15;        // 15-minute intervals
const TOTAL_ITERATIONS = 100000;

// ==============================================
// According to the examples:
// 1 table = 4 seats
// 2 tables combined = 6 seats
// 3 tables combined = 8 seats
// Formula: combinedCapacity(n) = n*MAX_CAPACITY_PER_TABLE - (n-1)*2
int combinedCapacity(int numTables) {
  return numTables * MAX_CAPACITY_PER_TABLE - (numTables - 1) * 2;
}

// ==============================================
// Data Structures
// ==============================================

class ReservationDTO {
  String id;
  String name;
  String phone;
  int guests;
  int startTime;
  int endTime;
  List<int> tables;

  ReservationDTO(
      {required this.id,
        required this.name,
        required this.phone,
        required this.guests,
        required this.startTime,
        required this.endTime,
        required this.tables});
}

class TableData {
  int id;
  bool occupied;
  int timesUsed;

  TableData(this.id, {this.occupied = false, this.timesUsed = 0});
}

// ==============================================
// Random Data Generation
// ==============================================

final _rnd = Random();

int randomInt(int max) {
  if (max <= 0) return 0;
  return _rnd.nextInt(max);
}

String generateRandomName() {
  // 5-letter name (a-z)
  var chars = List.generate(5, (index) => String.fromCharCode(97 + randomInt(26)));
  return chars.join();
}

String generateRandomPhone() {
  // 9-digit number
  var digits = List.generate(9, (index) => randomInt(10).toString());
  return digits.join();
}

String generateId64Hex() {
  // 64 hex chars = 32 bytes
  // If we cannot use external packages or crypto, just do random hex:
  // Here, we'll just generate 32 random bytes and convert to hex.
  var bytes = Uint8List(32);
  for (int i = 0; i < 32; i++) {
    bytes[i] = randomInt(256);
  }
  // Convert to hex:
  final hexChars = '0123456789abcdef';
  var sb = StringBuffer();
  for (var b in bytes) {
    sb.write(hexChars[b >> 4]);
    sb.write(hexChars[b & 0x0F]);
  }
  return sb.toString();
}

class Customer {
  String name;
  String phone;
  Customer(this.name, this.phone);
}

List<Customer> generateCustomers(int num) {
  var list = <Customer>[];
  for (int i = 0; i < num; i++) {
    list.add(Customer(generateRandomName(), generateRandomPhone()));
  }
  return list;
}

// ==============================================
// Helper Functions
// ==============================================

bool checkAllTablesUsedOnce(List<TableData> tables) {
  for (var t in tables) {
    if (t.timesUsed == 0) {
      return false;
    }
  }
  return true;
}

List<int>? findTablesForGuests(int guestCount, List<TableData> tables) {
  for (int n = 1; n <= NUM_TABLES; n++) {
    var cap = combinedCapacity(n);
    if (cap >= guestCount) {
      var freeTablesIndices = <int>[];
      for (int i = 0; i < tables.length && freeTablesIndices.length < n; i++) {
        if (!tables[i].occupied) {
          freeTablesIndices.add(i);
        }
      }
      if (freeTablesIndices.length == n) {
        return freeTablesIndices;
      }
    }
  }
  return null;
}

ReservationDTO? occupyTables(List<int> tablesIndices, int guests, int startTime,
    List<TableData> tables, List<ReservationDTO> reservations, List<Customer> customers) {
  var occupationTime = 60 + guests * 5;
  var endTime = startTime + occupationTime;

  for (var idx in tablesIndices) {
    tables[idx].occupied = true;
    tables[idx].timesUsed += 1;
  }

  var randomCustomer = customers[randomInt(customers.length)];

  var res = ReservationDTO(
      id: generateId64Hex(),
      name: randomCustomer.name,
      phone: randomCustomer.phone,
      guests: guests,
      startTime: startTime,
      endTime: endTime,
      tables: List<int>.from(tablesIndices));
  reservations.add(res);
  return res;
}

bool cancelReservation(String resId, List<ReservationDTO> reservations, List<TableData> tables) {
  for (int i = 0; i < reservations.length; i++) {
    if (reservations[i].id == resId) {
      var res = reservations[i];
      for (var ti in res.tables) {
        tables[ti].occupied = false;
      }
      // remove reservation by swapping with last
      reservations[i] = reservations.last;
      reservations.removeLast();
      return true;
    }
  }
  return false;
}

ReservationDTO? modifyReservation(String resId, int newGuests, int newStartTime,
    List<ReservationDTO> reservations, List<TableData> tables, List<Customer> customers, List<int> timeSlots) {
  if (!cancelReservation(resId, reservations, tables)) {
    return null;
  }
  var t = findTablesForGuests(newGuests, tables);
  if (t == null) return null;
  return occupyTables(t, newGuests, newStartTime, tables, reservations, customers);
}

List<int> generateTimeSlots() {
  var slots = <int>[];
  for (int t = START_TIME; t <= END_TIME; t += INTERVAL) {
    slots.add(t);
  }
  return slots;
}

Map<String, dynamic> runSimulation(List<Customer> customers, List<int> timeSlots) {
  var tables = List.generate(NUM_TABLES, (i) => TableData(i));
  var reservations = <ReservationDTO>[];
  int callCount = 0;
  bool allTablesUsedOnce = false;

  while (!allTablesUsedOnce) {
    callCount++;
    var guests = 1 + randomInt(15); // 1 to 15
    var startTime = timeSlots[randomInt(timeSlots.length)];

    if (callCount % 20 == 0 && reservations.isNotEmpty) {
      // Every 20 calls, cancel a reservation
      var rndRes = reservations[randomInt(reservations.length)].id;
      cancelReservation(rndRes, reservations, tables);
    } else if (callCount % 10 == 0 && reservations.isNotEmpty) {
      // Every 10 calls (not 20), modify a reservation
      var rndRes = reservations[randomInt(reservations.length)].id;
      var newGuests = 1 + randomInt(15);
      var newStartTime = timeSlots[randomInt(timeSlots.length)];
      modifyReservation(rndRes, newGuests, newStartTime, reservations, tables, customers, timeSlots);
    } else {
      // Normal reservation attempt
      var t = findTablesForGuests(guests, tables);
      if (t != null) {
        occupyTables(t, guests, startTime, tables, reservations, customers);
      }
    }

    allTablesUsedOnce = checkAllTablesUsedOnce(tables);
    if (callCount > 100000) {
      break;
    }
  }

  return {
    'totalReservations': reservations.length,
    'totalCalls': callCount,
    'allUsed': allTablesUsedOnce
  };
}

void main() {
  var customers = generateCustomers(NUM_CUSTOMERS);
  var timeSlots = generateTimeSlots();

  for (int i = 0; i < TOTAL_ITERATIONS; i++) {
    runSimulation(customers, timeSlots);
  }

  print("All iterations completed.");
}
