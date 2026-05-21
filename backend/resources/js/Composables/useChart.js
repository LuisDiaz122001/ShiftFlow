import { ref } from 'vue';
import {
    Chart,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
} from 'chart.js';

let isChartJsRegistered = false;

const registerChartJs = () => {
    if (isChartJsRegistered) {
        return;
    }

    Chart.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend);
    isChartJsRegistered = true;
};

const isValidChartData = (data) => {
    return data && Array.isArray(data.labels) && Array.isArray(data.values);
};

export default function useChart() {
    const chartInstance = ref(null);

    const destroyChart = () => {
        if (chartInstance.value) {
            chartInstance.value.destroy();
            chartInstance.value = null;
        }
    };

    const createChart = (canvasRef, data, config = {}) => {
        registerChartJs();

        if (!canvasRef?.value || !isValidChartData(data)) {
            destroyChart();
            return null;
        }

        const context = canvasRef.value.getContext('2d');
        if (!context) {
            return null;
        }

        destroyChart();

        const dataset = {
            label: config.label ?? 'Datos',
            data: data.values,
            borderColor: config.borderColor ?? '#6366F1',
            backgroundColor: config.backgroundColor ?? 'rgba(99, 102, 241, 0.2)',
            fill: config.fill ?? true,
            tension: config.tension ?? 0.35,
            pointRadius: config.pointRadius ?? 4,
            pointHoverRadius: config.pointHoverRadius ?? 6,
            ...config.dataset,
        };

        chartInstance.value = new Chart(context, {
            type: config.type ?? 'line',
            data: {
                labels: data.labels,
                datasets: [dataset],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: config.xAxisLabel ?? 'Fecha',
                        },
                        ticks: {
                            maxRotation: 0,
                            autoSkip: true,
                            maxTicksLimit: 10,
                        },
                    },
                    y: {
                        title: {
                            display: true,
                            text: config.yAxisLabel ?? 'Horas',
                        },
                        beginAtZero: true,
                    },
                },
                plugins: {
                    legend: {
                        display: config.showLegend ?? false,
                    },
                    tooltip: {
                        callbacks: {
                            label: config.tooltipLabel
                                ? (context) => config.tooltipLabel(context)
                                : (context) => `${context.parsed.y} h`,
                        },
                    },
                },
                ...config.options,
            },
        });

        return chartInstance.value;
    };

    const updateChart = (data, config = {}) => {
        if (!chartInstance.value || !isValidChartData(data)) {
            if (chartInstance.value && !isValidChartData(data)) {
                destroyChart();
            }
            return;
        }

        chartInstance.value.data.labels = data.labels;
        chartInstance.value.data.datasets[0].data = data.values;
        if (config.label) {
            chartInstance.value.data.datasets[0].label = config.label;
        }
        chartInstance.value.update();
    };

    const renderChart = (canvasRef, data, config = {}) => {
        if (!chartInstance.value) {
            return createChart(canvasRef, data, config);
        }

        updateChart(data, config);
        return chartInstance.value;
    };

    return {
        chartInstance,
        createChart,
        updateChart,
        destroyChart,
        renderChart,
    };
}
