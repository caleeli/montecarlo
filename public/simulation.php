<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monte Carlo Simulation - Jira Tickets</title>
    <script src="https://cdn.jsdelivr.net/npm/vue@3.3.4/dist/vue.global.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            color: white;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .main-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .card h2 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
            font-family: 'Courier New', monospace;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .small-input {
            max-width: 200px;
        }

        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            display: block;
            width: 100%;
            margin-top: 20px;
        }

        .btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .loading {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .spinner {
            border: 3px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .results-container {
            grid-column: 1 / -1;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
        }

        .table-container {
            overflow-x: auto;
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        th {
            background: #f5f5f5;
            color: #667eea;
            font-weight: 600;
        }

        tr:hover {
            background: #f9f9f9;
        }

        .chart-container {
            margin-top: 30px;
            height: 300px;
        }

        .error {
            background: #ff4444;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .tag {
            display: inline-block;
            padding: 4px 10px;
            background: #e0e0e0;
            border-radius: 4px;
            margin-right: 5px;
            margin-bottom: 5px;
            font-size: 0.85rem;
        }

        .tickets-input {
            width: 100%;
            margin-bottom: 10px;
        }

        .add-ticket-btn {
            background: #4CAF50;
            padding: 8px 15px;
            font-size: 0.9rem;
            margin-top: 5px;
        }

        .ticket-row {
            display: grid;
            grid-template-columns: 2fr 1fr auto;
            gap: 10px;
            margin-bottom: 10px;
            align-items: center;
        }

        .remove-btn {
            background: #ff4444;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
        }

        .full-width {
            grid-column: 1 / -1;
        }

        .clickable-stat {
            cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .clickable-stat:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.35);
        }

        .selected-stat {
            outline: 3px solid #ffd54f;
        }

        .planning-table th.section-divider {
            background: #eef1ff;
            color: #4756b3;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .planning-table td:nth-child(2) {
            width: 180px;
        }
    </style>
</head>
<body>
    <div id="app">
        <div class="container">
            <div class="header">
                <h1>🎲 Monte Carlo Simulation</h1>
                <p>Simulation of project duration based on historical data from Jira</p>
            </div>

            <div class="main-content">
                <!-- Configuration Panel -->
                <div class="card">
                    <h2>⚙️ Configuration</h2>
                    
                    <div class="form-group">
                        <label>JQL Query</label>
                        <textarea 
                            v-model="config.jql" 
                            placeholder='project = "MyProject" AND issuetype in (Story, Task)...'
                            rows="4">
                        </textarea>
                    </div>

                    <div class="form-group">
                        <label>In Progress Status (comma separated)</label>
                        <input 
                            v-model="config.inProgressStatus" 
                            type="text" 
                            placeholder="In Progress, Scheduled, Research">
                    </div>

                    <div class="form-group">
                        <label>Done Status (comma separated)</label>
                        <input 
                            v-model="config.doneStatuses" 
                            type="text" 
                            placeholder="Done, Closed, Resolved, Wont Fix">
                    </div>

                    <div class="form-group">
                        <label>Iteraciones</label>
                        <input 
                            v-model.number="config.iterations" 
                            type="number" 
                            class="small-input"
                            min="1000"
                            max="100000">
                    </div>

                    <div class="form-group">
                        <label>Max Results (Jira)</label>
                        <input 
                            v-model.number="config.maxResults" 
                            type="number" 
                            class="small-input"
                            min="10"
                            max="500">
                    </div>

                    <div class="form-group">
                        <label>Max Multiplier</label>
                        <input 
                            v-model.number="config.maxMultiplier" 
                            type="number" 
                            class="small-input"
                            min="1"
                            step="0.1">
                    </div>

                    <div class="form-group">
                        <label>Segments to Include in Simulation</label>
                        <div style="display: flex; gap: 15px; margin-top: 10px;">
                            <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                                <input type="checkbox" v-model="config.segmentS" value="S">
                                <span>S (0..1]</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                                <input type="checkbox" v-model="config.segmentM" value="M">
                                <span>M (1,3]</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                                <input type="checkbox" v-model="config.segmentL" value="L">
                                <span>L (3,∞)</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Project Tickets Panel -->
                <div class="card">
                    <h2>📋 Project Tickets</h2>
                    
                    <div class="form-group">
                        <label>Import from Clipboard</label>
                        <textarea 
                            v-model="importText" 
                            placeholder="Paste tickets here (one per line)&#10;Format: TICKET-KEY[TAB or COMMA]DAYS&#10;Example:&#10;TICKET-1	2.5&#10;TICKET-2,3&#10;TICKET-3	1.5"
                            rows="4"
                            style="font-size: 12px;">
                        </textarea>
                        <button @click="importTickets" class="btn add-ticket-btn" style="background: #2196F3; margin-top: 10px;">
                            📥 Import Tickets
                        </button>
                    </div>

                    <div class="form-group">
                        <label>Add Tickets Manually</label>
                        <div v-for="(ticket, index) in projectTickets" :key="index" class="ticket-row">
                            <input 
                                v-model="ticket.key" 
                                type="text" 
                                placeholder="Key (ej: NEW-1)">
                            <input 
                                v-model.number="ticket.estimate_days" 
                                type="number" 
                                placeholder="days estimados"
                                step="0.5"
                                min="0.5">
                            <button @click="removeTicket(index)" class="remove-btn">✕</button>
                        </div>
                        <button @click="addTicket" class="btn add-ticket-btn">+ Add Ticket</button>
                    </div>
                    
                    <div style="margin-top: 20px; padding: 15px; background: #f5f5f5; border-radius: 8px; text-align: center;">
                        <div style="color: #666; font-size: 0.9rem; margin-bottom: 5px;">Total Estimated Time</div>
                        <div style="color: #667eea; font-size: 1.8rem; font-weight: bold;">{{ totalEstimatedDays.toFixed(1) }} days</div>
                    </div>
                </div>

                <!-- Run Simulation Button -->
                <div class="card full-width">
                    <button 
                        @click="runSimulation" 
                        :disabled="loading"
                        class="btn">
                        <span v-if="!loading">🚀 Run Simulation</span>
                        <span v-else class="loading">
                            <span class="spinner"></span>
                            Running...
                        </span>
                    </button>
                </div>

                <!-- Error Message -->
                <div v-if="error" class="card full-width">
                    <div class="error">
                        <strong>Error:</strong> {{ error }}
                    </div>
                </div>

                <!-- Results Panel -->
                <div v-if="results" class="card results-container">
                    <h2>📊 Simulation Results</h2>

                    <!-- Segments Statistics -->
                    <div v-if="results.segments" style="margin-bottom: 30px;">
                        <h3 style="color: #667eea; margin-bottom: 15px;">
                            Multiplier Segments Distribution 
                            <span style="font-size: 0.9rem; color: #666;">(Using: {{ results.enabledSegments.join(', ') }})</span>
                        </h3>
                        <div class="stats-grid">
                            <div v-for="(segment, key) in results.segments" :key="key" 
                                 class="stat-card"
                                 :style="{ opacity: results.enabledSegments.includes(key) ? 1 : 0.4 }">
                                <div class="stat-label">{{ key }} - {{ segment.range }}</div>
                                <div class="stat-value" style="font-size: 1.5rem;">{{ segment.count }}</div>
                                <div style="margin-top: 10px; font-size: 0.85rem;">
                                    <div>{{ segment.percentage.toFixed(1) }}% of total</div>
                                    <div v-if="segment.count > 0">Mean: {{ segment.mean.toFixed(2) }}x</div>
                                    <div v-if="segment.count > 0">Range: {{ segment.min.toFixed(2) }} - {{ segment.max.toFixed(2) }}</div>
                                </div>
                            </div>
                        </div>
                        <div style="text-align: center; margin-top: 15px; padding: 10px; background: #f5f5f5; border-radius: 8px;">
                            <strong>Active multipliers in simulation:</strong> {{ results.activeMultipliersCount }} / {{ results.multipliers.count }}
                        </div>
                    </div>
                    
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-label">Iterations</div>
                            <div class="stat-value">{{ results.stats.iterations }}</div>
                        </div>
                        <div 
                            class="stat-card clickable-stat"
                            :class="{ 'selected-stat': selectedResultMetric === 'min' }"
                            @click="selectResultMetric('min')">
                            <div class="stat-label">Minimum</div>
                            <div class="stat-value">{{ formatNumber(results.stats.min) }} days</div>
                        </div>
                        <div 
                            class="stat-card clickable-stat"
                            :class="{ 'selected-stat': selectedResultMetric === 'mean' }"
                            @click="selectResultMetric('mean')">
                            <div class="stat-label">Mean</div>
                            <div class="stat-value">{{ formatNumber(results.stats.mean) }} days</div>
                        </div>
                        <div 
                            class="stat-card clickable-stat"
                            :class="{ 'selected-stat': selectedResultMetric === 'p50' }"
                            @click="selectResultMetric('p50')">
                            <div class="stat-label">P50 (Median)</div>
                            <div class="stat-value">{{ formatNumber(results.stats.p50) }} days</div>
                        </div>
                        <div 
                            class="stat-card clickable-stat"
                            :class="{ 'selected-stat': selectedResultMetric === 'p80' }"
                            @click="selectResultMetric('p80')">
                            <div class="stat-label">P80</div>
                            <div class="stat-value">{{ formatNumber(results.stats.p80) }} days</div>
                        </div>
                        <div 
                            class="stat-card clickable-stat"
                            :class="{ 'selected-stat': selectedResultMetric === 'p90' }"
                            @click="selectResultMetric('p90')">
                            <div class="stat-label">P90</div>
                            <div class="stat-value">{{ formatNumber(results.stats.p90) }} days</div>
                        </div>
                        <div 
                            class="stat-card clickable-stat"
                            :class="{ 'selected-stat': selectedResultMetric === 'p95' }"
                            @click="selectResultMetric('p95')">
                            <div class="stat-label">P95</div>
                            <div class="stat-value">{{ formatNumber(results.stats.p95) }} days</div>
                        </div>
                        <div 
                            class="stat-card clickable-stat"
                            :class="{ 'selected-stat': selectedResultMetric === 'max' }"
                            @click="selectResultMetric('max')">
                            <div class="stat-label">Maximum</div>
                            <div class="stat-value">{{ formatNumber(results.stats.max) }} days</div>
                        </div>
                    </div>
                    <div style="margin: -15px 0 25px; color: #555; font-size: 0.95rem;">
                        Click one duration result card (`Minimum`, `Mean`, `P50`, `P80`, `P90`, `P95`, `Maximum`) to calculate the planning table.
                    </div>

                    <div v-if="selectedResultMetric && planningTable" class="card full-width" style="padding: 20px; margin-bottom: 25px;">
                        <h3 style="color: #667eea; margin-bottom: 15px;">
                            Delivery Planning Table ({{ selectedResultMetric.toUpperCase() }})
                        </h3>
                        <div class="table-container" style="margin-top: 0;">
                            <table class="planning-table">
                                <thead>
                                    <tr>
                                        <th>Field</th>
                                        <th>Value</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Total simulation days</td>
                                        <td>{{ formatNumber(planningTable.totalSimulationDays) }}</td>
                                        <td>From selected percentile result.</td>
                                    </tr>
                                    <tr>
                                        <td>Holidays and PTOs</td>
                                        <td>
                                            <input v-model.number="planningInputs.holidaysAndPtos" type="number" min="0" step="1">
                                        </td>
                                        <td>Editable.</td>
                                    </tr>
                                    <tr>
                                        <td>Maintainance days</td>
                                        <td>
                                            <input v-model.number="planningInputs.maintenanceDays" type="number" min="0" step="1">
                                        </td>
                                        <td>Default: round(total simulation days / 5). Editable.</td>
                                    </tr>
                                    <tr>
                                        <td>Total days</td>
                                        <td>{{ planningTable.totalDays }}</td>
                                        <td>Total simulation + holidays/PTOs + maintainance.</td>
                                    </tr>
                                    <tr>
                                        <th colspan="3" class="section-divider">Section Division</th>
                                    </tr>
                                    <tr>
                                        <td>Developers assigned</td>
                                        <td>
                                            <input v-model.number="planningInputs.developersAssigned" type="number" min="1" step="1">
                                        </td>
                                        <td>Editable.</td>
                                    </tr>
                                    <tr>
                                        <td>QA assigned</td>
                                        <td>
                                            <input v-model.number="planningInputs.qaAssigned" type="number" min="1" step="1">
                                        </td>
                                        <td>Editable.</td>
                                    </tr>
                                    <tr>
                                        <td>QA overhead (%)</td>
                                        <td>
                                            <input v-model.number="planningInputs.qaOverheadPercent" type="number" min="0" max="100" step="1">
                                        </td>
                                        <td>Extra QA workload on top of development. Default: 25%. Editable.</td>
                                    </tr>
                                    <tr>
                                        <th colspan="3" class="section-divider">Section Division</th>
                                    </tr>
                                    <tr>
                                        <td>Business effort days</td>
                                        <td>{{ planningTable.businessDaysOfTeam }}</td>
                                        <td>round(total days * (1 + QA overhead)).</td>
                                    </tr>
                                    <tr>
                                        <td>Contingencies / Risk (%)</td>
                                        <td>
                                            <input v-model.number="planningInputs.contingenciesRiskPercent" type="number" min="0" max="100" step="1">
                                        </td>
                                        <td>Default: 5%. Editable.</td>
                                    </tr>
                                    <tr>
                                        <td>Development weeks</td>
                                        <td>{{ planningTable.developmentWeeks }}</td>
                                        <td>ceil((total days/developers assigned/5)*(1+risk)).</td>
                                    </tr>
                                    <tr>
                                        <td>QA weeks</td>
                                        <td>{{ planningTable.qaWeeks }}</td>
                                        <td>ceil(((total days/QA assigned)*QA overhead/5)*(1+risk)).</td>
                                    </tr>
                                    <tr>
                                        <td>Start date</td>
                                        <td>
                                            <input v-model="planningInputs.startDate" type="date">
                                        </td>
                                        <td>Editable.</td>
                                    </tr>
                                    <tr>
                                        <td>Estimated end date</td>
                                        <td>{{ planningTable.estimatedEndDate }}</td>
                                        <td>Start date + development weeks + QA weeks.</td>
                                    </tr>
                                    <tr>
                                        <th colspan="3" class="section-divider">Section Division</th>
                                    </tr>
                                    <tr>
                                        <td>T-shirt size</td>
                                        <td>{{ planningTable.tshirtSize }}</td>
                                        <td>S: 1-5 days, M: 1-4 weeks, L: 1-3 months, XL: 3+ months.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <h3 style="margin-top: 30px; color: #667eea;">Distribution of Results</h3>
                    <div class="chart-container">
                        <canvas ref="chartCanvas"></canvas>
                    </div>

                    <h3 style="margin-top: 30px; color: #667eea;">
                        Reference Historical Tickets ({{ results.historicalTickets.length }} tickets)
                    </h3>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Key</th>
                                    <th>Estimated (days)</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Final Status</th>
                                    <th>Actual Days</th>
                                    <th>Multiplier</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="ticket in results.historicalTickets" :key="ticket.key">
                                    <td><a :href="'https://processmaker.atlassian.net/browse/' + ticket.key" target="_blank">{{ ticket.key }}</a></td>
                                    <td>{{ formatNumber(ticket.estimate_days) }}</td>
                                    <td>{{ formatDate(ticket.start_date) }}</td>
                                    <td>{{ formatDate(ticket.end_date) }}</td>
                                    <td>{{ ticket.done_status }}</td>
                                    <td>{{ ticket.business_days }}</td>
                                    <td>{{ formatNumber(ticket.business_days / ticket.estimate_days) }}x</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const { createApp } = Vue;

        createApp({
            data() {
                return {
                    config: {
                        jql: 'project = "ProcessMaker 4" AND issuetype in (Story, Task) AND status in (Done, Closed, Resolved, "Final Review", Merged) AND created >= "2024-01-01" AND created <= "2025-07-31" and "Story Points[Number]">1',
                        inProgressStatus: 'In Progress, Scheduled, Research',
                        doneStatuses: 'Done, Closed, Resolved, Wont Fix, Merged, Final Review',
                        iterations: 10000,
                        maxResults: 500,
                        maxMultiplier: 10,
                        segmentS: true,
                        segmentM: true,
                        segmentL: false
                    },
                    projectTickets: [
                        { key: 'NEW-1', estimate_days: 2 }
                    ],
                    importText: '',
                    loading: false,
                    error: null,
                    results: null,
                    chart: null,
                    selectedResultMetric: null,
                    planningInputs: {
                        holidaysAndPtos: 0,
                        maintenanceDays: 0,
                        developersAssigned: 1,
                        qaAssigned: 1,
                        qaOverheadPercent: 25,
                        contingenciesRiskPercent: 5,
                        startDate: new Date().toISOString().slice(0, 10)
                    }
                }
            },
            computed: {
                totalEstimatedDays() {
                    return this.projectTickets.reduce((sum, ticket) => {
                        return sum + (parseFloat(ticket.estimate_days) || 0);
                    }, 0);
                },
                planningTable() {
                    if (!this.results || !this.selectedResultMetric || !this.results.stats) return null;

                    const totalSimulationDays = Number(this.results.stats[this.selectedResultMetric] || 0);
                    const holidaysAndPtos = this.safeNumber(this.planningInputs.holidaysAndPtos);
                    const maintenanceDays = this.safeNumber(this.planningInputs.maintenanceDays);
                    const totalDays = Math.round(totalSimulationDays + holidaysAndPtos + maintenanceDays);

                    const qaOverhead = this.safeNumber(this.planningInputs.qaOverheadPercent) / 100;
                    const contingenciesRisk = this.safeNumber(this.planningInputs.contingenciesRiskPercent) / 100;
                    const developersAssigned = Math.max(1, this.safeNumber(this.planningInputs.developersAssigned));
                    const qaAssigned = Math.max(1, this.safeNumber(this.planningInputs.qaAssigned));

                    const businessDaysOfTeam = Math.round(totalDays * (1 + qaOverhead));
                    const developmentWeeks = Math.ceil(((totalDays / developersAssigned) / 5) * (1 + contingenciesRisk));
                    const qaWeeks = Math.ceil((((totalDays / qaAssigned) * qaOverhead) / 5) * (1 + contingenciesRisk));
                    const totalCalendarWeeks = developmentWeeks + qaWeeks;

                    const startDate = this.planningInputs.startDate ? new Date(this.planningInputs.startDate) : new Date();
                    const estimatedEndDateObj = new Date(startDate);
                    estimatedEndDateObj.setDate(startDate.getDate() + ((developmentWeeks + qaWeeks) * 7));

                    return {
                        totalSimulationDays,
                        totalDays,
                        businessDaysOfTeam,
                        developmentWeeks,
                        qaWeeks,
                        estimatedEndDate: estimatedEndDateObj.toLocaleDateString('es-ES', {
                            year: 'numeric',
                            month: 'short',
                            day: 'numeric'
                        }),
                        tshirtSize: this.calculateTshirtSize(totalDays, totalCalendarWeeks)
                    };
                }
            },
            methods: {
                addTicket() {
                    this.projectTickets.push({
                        key: `NEW-${this.projectTickets.length + 1}`,
                        estimate_days: 1
                    });
                },
                removeTicket(index) {
                    this.projectTickets.splice(index, 1);
                },
                importTickets() {
                    if (!this.importText.trim()) {
                        alert('Please paste some tickets in the text area');
                        return;
                    }

                    const lines = this.importText.trim().split('\n');
                    let imported = 0;
                    let errors = [];
                    const newTickets = []; // Temporary array for new tickets

                    lines.forEach((line, lineNum) => {
                        line = line.trim();
                        if (!line) return; // Skip empty lines

                        // Try to split by tab first, then by comma
                        let parts = line.split('\t');
                        if (parts.length < 2) {
                            parts = line.split(',');
                        }

                        if (parts.length >= 2) {
                            const key = parts[0].trim();
                            const days = parseFloat(parts[1].trim());

                            if (key && !isNaN(days) && days > 0) {
                                newTickets.push({
                                    key: key,
                                    estimate_days: days
                                });
                                imported++;
                            } else {
                                errors.push(`Line ${lineNum + 1}: Invalid format - "${line}"`);
                            }
                        } else {
                            errors.push(`Line ${lineNum + 1}: Could not parse - "${line}"`);
                        }
                    });

                    // Show result message
                    if (imported > 0) {
                        // Replace all tickets with the new ones
                        this.projectTickets = newTickets;
                        this.importText = ''; // Clear the text area after successful import
                        
                        // Only show alert if there were errors
                        if (errors.length > 0) {
                            alert(`⚠️ ${errors.length} line(s) could not be imported:\n${errors.join('\n')}`);
                        }
                    } else {
                        alert('❌ No tickets could be imported.\n\n' + errors.join('\n'));
                    }
                },
                async runSimulation() {
                    this.loading = true;
                    this.error = null;
                    this.results = null;
                    this.selectedResultMetric = null;

                    // Build enabled segments array
                    const enabledSegments = [];
                    if (this.config.segmentS) enabledSegments.push('S');
                    if (this.config.segmentM) enabledSegments.push('M');
                    if (this.config.segmentL) enabledSegments.push('L');

                    // Validate at least one segment is selected
                    if (enabledSegments.length === 0) {
                        this.error = 'Please select at least one segment (S, M, or L)';
                        this.loading = false;
                        return;
                    }

                    try {
                        const response = await fetch('api.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                jql: this.config.jql,
                                inProgressStatus: this.config.inProgressStatus.split(',').map(s => s.trim()),
                                doneStatuses: this.config.doneStatuses.split(',').map(s => s.trim()),
                                projectTickets: this.projectTickets,
                                iterations: this.config.iterations,
                                maxResults: this.config.maxResults,
                                maxMultiplier: this.config.maxMultiplier,
                                enabledSegments: enabledSegments
                            })
                        });

                        const data = await response.json();

                        if (!response.ok) {
                            throw new Error(data.error || 'Error en la simulación');
                        }

                        this.results = data;
                        this.resetPlanningInputs();
                        
                        // Wait for next tick to ensure canvas is rendered
                        this.$nextTick(() => {
                            this.renderChart();
                        });

                    } catch (err) {
                        this.error = err.message;
                    } finally {
                        this.loading = false;
                    }
                },
                formatNumber(num) {
                    return Number(num).toFixed(1);
                },
                formatDate(dateString) {
                    const date = new Date(dateString);
                    return date.toLocaleDateString('es-ES', { 
                        year: 'numeric', 
                        month: 'short', 
                        day: 'numeric' 
                    });
                },
                safeNumber(value) {
                    const num = Number(value);
                    return Number.isFinite(num) ? num : 0;
                },
                selectResultMetric(metricKey) {
                    if (!this.results || !this.results.stats || typeof this.results.stats[metricKey] === 'undefined') return;

                    this.selectedResultMetric = metricKey;
                    this.planningInputs.maintenanceDays = Math.round(this.safeNumber(this.results.stats[metricKey]) / 5);
                },
                resetPlanningInputs() {
                    this.planningInputs = {
                        holidaysAndPtos: 0,
                        maintenanceDays: 0,
                        developersAssigned: 1,
                        qaAssigned: 1,
                        qaOverheadPercent: 25,
                        contingenciesRiskPercent: 5,
                        startDate: new Date().toISOString().slice(0, 10)
                    };
                },
                calculateTshirtSize(totalDays, totalCalendarWeeks) {
                    if (totalDays <= 5) return 'S';
                    if (totalCalendarWeeks <= 4) return 'M';
                    if (totalCalendarWeeks < 12) return 'L';
                    return 'XL';
                },
                renderChart() {
                    if (!this.results || !this.$refs.chartCanvas) return;

                    // Destroy previous chart if exists
                    if (this.chart) {
                        this.chart.destroy();
                    }

                    // Create histogram data
                    const samples = this.results.samples;
                    const bins = 30;
                    const min = Math.min(...samples);
                    const max = Math.max(...samples);
                    const binSize = (max - min) / bins;
                    
                    const histogram = new Array(bins).fill(0);
                    const labels = [];
                    
                    for (let i = 0; i < bins; i++) {
                        labels.push((min + i * binSize).toFixed(1));
                    }
                    
                    samples.forEach(value => {
                        const binIndex = Math.min(Math.floor((value - min) / binSize), bins - 1);
                        histogram[binIndex]++;
                    });

                    const ctx = this.$refs.chartCanvas.getContext('2d');
                    this.chart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Frecuencia',
                                data: histogram,
                                backgroundColor: 'rgba(102, 126, 234, 0.6)',
                                borderColor: 'rgba(102, 126, 234, 1)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                title: {
                                    display: true,
                                    text: 'Distribution of Project Duration (days)'
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: 'Frequency'
                                    }
                                },
                                x: {
                                    title: {
                                        display: true,
                                        text: 'Duration (days)'
                                    }
                                }
                            }
                        }
                    });
                }
            }
        }).mount('#app');
    </script>
</body>
</html>
