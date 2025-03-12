# Carbon Footprint Tracker

A personal energy and transportation monitoring application that helps users track, analyze, and reduce their carbon footprint.

## Features

- **User Authentication**: Secure login and registration system
- **Energy Usage Tracking**: Log daily household energy consumption
- **Transport Monitoring**: Record transportation methods and distances
- **Carbon Footprint Calculation**: Automatic calculation based on energy and transport data
- **Personalized Recommendations**: Get tailored suggestions to reduce your carbon footprint
- **Visual Reports**: View your progress with charts and detailed breakdowns
- **Data Export**: Download your carbon footprint report as a text file

## Technologies Used

- PHP 7.4+
- PostgreSQL Database
- HTML5, CSS3, JavaScript
- Chart.js for data visualization
- Responsive design for mobile and desktop

## Installation

### Prerequisites

- XAMPP (with PHP 7.4 or higher)
- PostgreSQL database
- Web browser

### Setup Instructions

1.  Clone the repository.
2.  Set up the PostgreSQL database:
    *   Start PostgreSQL server.
    *   Create a new database named `carbon_footprint`.
    *   Run the `db/setup.php` script to create the database tables.
3.  Configure the application:
    *   Copy `config.php` and set database connection details.
4.  Set up XAMPP (move project to `htdocs`, start Apache).
5.  Access the application at `http://localhost/carbon-footprint-tracker`.


## Usage

1. **Register/Login**: Create an account or log in to your existing account
2. **Dashboard**: View your carbon footprint summary and quick recommendations
3. **Log Energy**: Record your daily energy consumption
4. **Log Transport**: Track your transportation methods and distances
5. **View Report**: Generate detailed reports and download them as text files
6. **Get Recommendations**: Receive personalized suggestions to reduce your carbon footprint

## Database Structure

The application uses the following main tables:
- `users`: Stores user account information
- `energy_usage`: Tracks daily energy consumption
- `user_transport`: Records transportation data
- `transport_modes`: Contains different transportation methods and their emission factors
- `appliances`: Lists common household appliances and their power ratings
- `user_appliances`: Tracks which appliances users have and their usage

## Acknowledgements

- [Chart.js](https://www.chartjs.org/) for data visualization
- [XAMPP](https://www.apachefriends.org/) for the local development environment


