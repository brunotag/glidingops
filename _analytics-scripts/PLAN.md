# Weather-Flight Correlation Analysis

## Goal

Correlate the number of flights (daily, weekly, monthly) with historical weather data for Greytown, Wairarapa to quantify how much weather affects flying activity — especially on weekends.

## Approach

Build a standalone Python script (`_analytics-scripts/weather-correlation.py`) that:

1. Queries daily flight counts from the GlidingOps database
2. Fetches daily weather data from the Open-Meteo Historical API (free, no key)
3. Merges and analyzes correlation between weather conditions and flying activity
4. Generates CSV exports and PNG charts

## Connection Details

| Detail | Value |
|--------|-------|
| DB Host | `localhost:33060` (Vagrant MySQL port forward) |
| DB Credentials | Read from `config/database.php` (homestead/secret) |
| Python | 3.13.5 on Windows host |

## Files

| File | Purpose |
|------|---------|
| `_analytics-scripts/weather-correlation.py` | Main script |
| `_analytics-scripts/requirements.txt` | Python dependencies |
| `_analytics-scripts/output/` | Generated CSVs + PNG charts |

## Script Structure

```
weather-correlation.py
  CONFIG section (thresholds, paths)
  AlreadyCachedWeather() -> loads/saves weather_cache.json
  fetch_weather_from_api() -> calls Open-Meteo once, caches
  query_flights_from_db() -> reads config/database.php, queries DB
  merge_and_analyze() -> join by date, flag flyable/missed
  export_csvs() -> merged_data.csv, monthly_summary.csv, dayofweek_summary.csv
  generate_charts() -> 4-5 PNG charts
```

## Database Query

```sql
SELECT localdate, COUNT(*) as flight_count
FROM flights
WHERE org = 1 AND deleted = 0 AND type = 1
GROUP BY localdate
ORDER BY localdate;
```

Day-of-week derived from `localdate` in Python.

## Weather API

One call to Open-Meteo for Greytown coordinates (-41.08, 175.47):

```
GET https://archive-api.open-meteo.com/v1/archive
  ?latitude=-41.08&longitude=175.47
  &start_date=2016-06-01&end_date=today
  &daily=precipitation_sum,rain_sum,wind_speed_10m_max,wind_gusts_10m_max,cloud_cover_mean
  &timezone=Pacific/Auckland
```

Cached locally so API is called only once.

## Weather Thresholds

| Variable | Threshold | Logic |
|----------|-----------|-------|
| `precipitation_sum` | > 0.5 mm | Non-flyable due to rain |
| `wind_speed_10m_max` | > 30 km/h | Non-flyable due to wind |
| Combined | Rain OR Wind | `non_flyable = rain OR wind` |

All thresholds are constants at top of script — easy to tweak.

## Charts

| Chart | Type | Insight |
|-------|------|---------|
| monthly_flights_vs_flyable_days | Grouped bar | Each month: flight count vs number of flyable-weather days |
| dayofweek_weather_impact | Bar + scatter | Avg flights per day-of-week, overlaid with % non-flyable days |
| flights_vs_precipitation_and_wind | Scatter matrix | Each day: rain vs flight count, colored by wind speed |
| weekend_missed_days_by_month | Stacked bar | Monthly count of Sat+Sun days that were non-flyable |
| year_heatmap | Calendar heatmap | x=month, y=year, cell color = flight count |

## Output Files

### CSVs
- `merged_data.csv` — Every day with flight count + weather vars + flyable flag
- `monthly_summary.csv` — Per month: flights, flyable_days, missed_days
- `dayofweek_summary.csv` — Per day-of-week: avg flights, % flyable, avg precip

### PNGs
- `monthly_flights_vs_flyable_days.png`
- `dayofweek_weather_impact.png`
- `flights_vs_precipitation_and_wind.png`
- `weekend_missed_days_by_month.png`
- `year_heatmap.png`

## Dependencies (`requirements.txt`)

```
pandas>=1.5.0
matplotlib>=3.5.0
requests>=2.28.0
pymysql>=1.0.0
```

## Usage

```bash
cd _analytics-scripts
pip install -r requirements.txt
python weather-correlation.py
```
