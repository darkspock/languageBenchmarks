package main

import (
	crand "crypto/rand" // crypto/rand for random bytes
	"encoding/hex"
	"fmt"
	mrand "math/rand"   // math/rand for pseudo-random integers
	"time"
)

// ==============================================
// Configuration and Data Structures (Constants)
// ==============================================

const (
	NUM_TABLES           = 100
	MAX_CAPACITY_PER_TABLE = 4
	NUM_CUSTOMERS        = 200
	START_TIME           = 12 * 60 // 12:00 in minutes from start of day
	END_TIME             = 22 * 60 // 22:00 in minutes
	INTERVAL             = 15      // 15-minute intervals
	TOTAL_ITERATIONS     = 100000
)

// According to the examples:
// 1 table = 4 seats
// 2 tables combined = 6 seats
// 3 tables combined = 8 seats
// General formula: combinedCapacity(n) = n*MAX_CAPACITY_PER_TABLE - (n-1)*2
func combinedCapacity(numTables int) int {
	return numTables*MAX_CAPACITY_PER_TABLE - (numTables-1)*2
}

// ==============================================
// Data Structures
// ==============================================

type ReservationDTO struct {
	ID        string
	Name      string
	Phone     string
	Guests    int
	StartTime int
	EndTime   int
	Tables    []int
}

type Table struct {
	ID        int
	Occupied  bool
	TimesUsed int
}

// Initialize random seed for math/rand
func init() {
	mrand.Seed(time.Now().UnixNano())
}

// randomInt generates a random int between 0 and max-1 using math/rand
func randomInt(max int) int {
	if max <= 0 {
		return 0
	}
	return mrand.Intn(max)
}

// generateRandomName returns a 5-letter random string (a-z)
func generateRandomName() string {
	name := make([]byte, 5)
	for i := 0; i < 5; i++ {
		name[i] = byte('a' + randomInt(26))
	}
	return string(name)
}

// generateRandomPhone returns a 9-digit random phone number as string
func generateRandomPhone() string {
	phone := make([]byte, 9)
	for i := 0; i < 9; i++ {
		phone[i] = byte('0' + randomInt(10))
	}
	return string(phone)
}

// generateId64Hex returns a 64-char hex ID
func generateId64Hex() string {
	// Use crypto/rand to generate 32 bytes
	b := make([]byte, 32)
	_, err := crand.Read(b)
	if err != nil {
		// Fallback if crypto fails, very unlikely
		for i := 0; i < 32; i++ {
			b[i] = byte(randomInt(256))
		}
	}
	return hex.EncodeToString(b)
}

type Customer struct {
	Name  string
	Phone string
}

func generateCustomers(num int) []Customer {
	customers := make([]Customer, num)
	for i := 0; i < num; i++ {
		customers[i] = Customer{
			Name:  generateRandomName(),
			Phone: generateRandomPhone(),
		}
	}
	return customers
}

func checkAllTablesUsedOnce(tables []Table) bool {
	for _, t := range tables {
		if t.TimesUsed == 0 {
			return false
		}
	}
	return true
}

func findTablesForGuests(guestCount int, tables []Table) []int {
	for n := 1; n <= NUM_TABLES; n++ {
		cap := combinedCapacity(n)
		if cap >= guestCount {
			freeTablesIndices := []int{}
			for i := 0; i < len(tables) && len(freeTablesIndices) < n; i++ {
				if !tables[i].Occupied {
					freeTablesIndices = append(freeTablesIndices, i)
				}
			}
			if len(freeTablesIndices) == n {
				return freeTablesIndices
			}
		}
	}
	return nil
}

func occupyTables(tablesIndices []int, guests, startTime int, tables []Table, reservations *[]ReservationDTO, customers []Customer) *ReservationDTO {
	occupationTime := 60 + guests*5
	endTime := startTime + occupationTime

	for _, idx := range tablesIndices {
		tables[idx].Occupied = true
		tables[idx].TimesUsed++
	}

	randomCustomer := customers[randomInt(len(customers))]

	res := ReservationDTO{
		ID:        generateId64Hex(),
		Name:      randomCustomer.Name,
		Phone:     randomCustomer.Phone,
		Guests:    guests,
		StartTime: startTime,
		EndTime:   endTime,
		Tables:    append([]int{}, tablesIndices...),
	}
	*reservations = append(*reservations, res)
	return &res
}

func cancelReservation(resId string, reservations *[]ReservationDTO, tables []Table) bool {
	resList := *reservations
	for i, r := range resList {
		if r.ID == resId {
			for _, ti := range r.Tables {
				tables[ti].Occupied = false
			}
			// remove reservation by swapping with last and slicing
			resList[i] = resList[len(resList)-1]
			*reservations = resList[:len(resList)-1]
			return true
		}
	}
	return false
}

func modifyReservation(resId string, newGuests, newStartTime int, reservations *[]ReservationDTO, tables []Table, customers []Customer, timeSlots []int) *ReservationDTO {
	if !cancelReservation(resId, reservations, tables) {
		return nil
	}
	t := findTablesForGuests(newGuests, tables)
	if t == nil {
		return nil
	}
	return occupyTables(t, newGuests, newStartTime, tables, reservations, customers)
}

func runSimulation(customers []Customer, timeSlots []int) (int, int, bool) {
	tables := make([]Table, NUM_TABLES)
	for i := 0; i < NUM_TABLES; i++ {
		tables[i] = Table{
			ID:        i,
			Occupied:  false,
			TimesUsed: 0,
		}
	}

	reservations := []ReservationDTO{}
	callCount := 0
	allTablesUsedOnce := false

	for !allTablesUsedOnce {
		callCount++
		guests := 1 + randomInt(15) // 1 to 15
		startTime := timeSlots[randomInt(len(timeSlots))]

		if callCount%20 == 0 && len(reservations) > 0 {
			// Every 20 calls, cancel a reservation
			rndRes := reservations[randomInt(len(reservations))].ID
			cancelReservation(rndRes, &reservations, tables)
		} else if callCount%10 == 0 && len(reservations) > 0 {
			// Every 10 calls (not 20), modify a reservation
			rndRes := reservations[randomInt(len(reservations))].ID
			newGuests := 1 + randomInt(15)
			newStartTime := timeSlots[randomInt(len(timeSlots))]
			modifyReservation(rndRes, newGuests, newStartTime, &reservations, tables, customers, timeSlots)
		} else {
			// Normal reservation attempt
			t := findTablesForGuests(guests, tables)
			if t != nil {
				occupyTables(t, guests, startTime, tables, &reservations, customers)
			}
		}

		allTablesUsedOnce = checkAllTablesUsedOnce(tables)
		if callCount > 100000 {
			break
		}
	}

	return len(reservations), callCount, allTablesUsedOnce
}

func main() {
	customers := generateCustomers(NUM_CUSTOMERS)

	timeSlots := []int{}
	for t := START_TIME; t <= END_TIME; t += INTERVAL {
		timeSlots = append(timeSlots, t)
	}

	for i := 0; i < TOTAL_ITERATIONS; i++ {
		runSimulation(customers, timeSlots)
	}
	fmt.Println("All iterations completed.")
}
