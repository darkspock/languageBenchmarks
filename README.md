# Objective

To test the performance between Node.js and PHP by simulating a restaurant table reservation system entirely in memory.

# Scenario Overview

## Tables

- The restaurant has **100 separate tables**.
- Each table can seat **up to 4 diners** when used individually.

## Joining Tables

- Tables can be joined together to accommodate larger groups.

### Seating Capacity Adjustment When Joining Tables

- When tables are joined, **each additional table after the first loses one seat**.
- This means:
  - **1 table** seats **4 diners**.
  - **2 tables** joined seat **6 diners**.
  - **3 tables** joined seat **8 diners**.
  - And so on, adding **2 additional seats** for each table beyond the first.

# Reservation Process

## Random Reservations

- Random groups of diners will make reservation requests.
- Group sizes range from **1 to 15 diners**, generated randomly.

## Loop Mechanics

The simulation runs a loop that continues until all tables are occupied (i.e., each table has at least one diner).

In each iteration of the loop:

1. **Reservation Request**
   - A group requests a table with a random group size between 1 and 15.

2. **Table Availability Check**
   - The system checks if there are enough free tables to accommodate the group based on the seating rules.
   - It calculates the minimum number of tables required for the group.

3. **Capacity Verification**
   - It verifies if the total seating capacity of the available tables meets the group's size.
   - If the capacity is insufficient, the group is informed that no suitable table is available.

4. **Table Assignment**
   - If suitable tables are available, they are assigned to the group.
   - The assigned tables are marked as occupied.

5. **Proceed to Next Request**
   - The loop moves on to handle the next reservation request.

# Seating Rules and Conditions

## Acceptable Table Occupancy

- It's acceptable for a table to be occupied by fewer than 4 diners.
- For example, a table with only **2 diners** is acceptable.

## Handling Large Groups

- If only a single table of 4 seats is left and a group of 5 requests a table, the group is informed that **no suitable table is available**.
- The next group with **4 or fewer diners** can be assigned the table.

## No Overbooking

- Groups cannot be split across non-joined tables.
- All diners in a group must be seated together at joined tables.

## Table Occupation Finality

- Once a table is occupied, it remains occupied for the duration of the simulation.
- There are **no table turnovers** or group departures.

# Termination Condition

- The simulation loop ends when **all 100 tables are occupied**, meaning every table has at least one diner assigned.

# Simulation Parameters

## Group Size Generation

- The number of diners in each group is randomly generated within the range of **1 to 15**.

## Performance Measurement

- The entire process is conducted **in memory** to test the computational performance of Node.js and PHP.
- **No external databases or file systems** are involved.

# Purpose of the Simulation

- To **compare the performance** of Node.js and PHP in handling complex data structures and algorithms in memory.
- To assess how **efficiently each environment manages resources** under the given simulation parameters.

---

### This is the prompt I used for ChatGPT:

> To test the performance between Node and PHP, I need a test (all in memory): I have a set of tables, each table can seat up to 4 diners. If tables are joined together, each one loses one seat. That is, 2 tables seat 6 diners; 3 tables seat 8 diners.
>
> We start with 100 tables that are separate. We receive random reservations until all tables are completely occupied. You need to create a loop that requests a free table and then assigns it.
>
> For example, if there's a table of 4 left and a group of 5 wants to eat, you tell them there's no availability, and the next group that requests with fewer than 5 is assigned the table. If there's a table left with 2 diners, that's okay. The loop ends when all tables have at least one diner. The random number of diners ranges from 1 to 15.
