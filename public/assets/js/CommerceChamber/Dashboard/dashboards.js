/**
 * Analytics Dashboard
 */

"use strict";
(function () {
  let cardColor, headingColor, axisColor, borderColor, shadeColor;

  if (isDarkStyle) {
    cardColor = config.colors_dark.cardColor;
    headingColor = config.colors_dark.headingColor;
    axisColor = config.colors_dark.axisColor;
    borderColor = config.colors_dark.borderColor;
    shadeColor = "dark";
  } else {
    cardColor = config.colors.white;
    headingColor = config.colors.headingColor;
    axisColor = config.colors.axisColor;
    borderColor = config.colors.borderColor;
    shadeColor = "light";
  }

  // Report Chart
  // --------------------------------------------------------------------

  // Radial bar chart functions
  function radialBarChart(color, value) {
    const radialBarChartOpt = {
      chart: {
        height: 50,
        width: 50,
        type: "radialBar",
      },
      plotOptions: {
        radialBar: {
          hollow: {
            size: "25%",
          },
          dataLabels: {
            show: false,
          },
          track: {
            background: borderColor,
          },
        },
      },
      stroke: {
        lineCap: "round",
      },
      colors: [color],
      grid: {
        padding: {
          top: -8,
          bottom: -10,
          left: -5,
          right: 0,
        },
      },
      series: [value],
      labels: ["Progress"],
    };
    return radialBarChartOpt;
  }

  const ReportchartList = document.querySelectorAll(".chart-report");
  if (ReportchartList) {
    ReportchartList.forEach(function (ReportchartEl) {
      const color = config.colors[ReportchartEl.dataset.color],
        series = ReportchartEl.dataset.series;
      const optionsBundle = radialBarChart(color, series);
      const reportChart = new ApexCharts(ReportchartEl, optionsBundle);
      reportChart.render();
    });
  }

  // Analytics - Bar Chart
  // --------------------------------------------------------------------
  const analyticsBarChartEl = document.querySelector("#thematicData"),
    analyticsBarChartConfig = {
      chart: {
        height: 250,
        type: "bar",
        toolbar: {
          show: false,
        },
      },
      plotOptions: {
        bar: {
          horizontal: false,
          columnWidth: "20%",
          borderRadius: 3,
          startingShape: "rounded",
        },
      },
      dataLabels: {
        enabled: false,
      },
      colors: ["#1976D2", "#1976D2"],
      grid: {
        borderColor: borderColor,
        padding: {
          bottom: -8,
        },
      },
      xaxis: {
        axisBorder: {
          show: false,
        },
        axisTicks: {
          show: false,
        },
        labels: {
          style: {
            colors: axisColor,
          },
        },
      },
      yaxis: {
        min: 0,
        max: 30,
        tickAmount: 3,
        labels: {
          style: {
            colors: axisColor,
          },
        },
      },
      legend: {
        position: "top", // You can change the position to 'bottom', 'left', or 'right' as needed
        markers: {
          width: 12,
          height: 12,
          strokeWidth: 0,
          radius: 12,
          fillColors: ["#4caf50", "#ff9800", "#1976D2"], // Define legend marker colors
        },
      },
      tooltip: {
        y: {
          formatter: function (val) {
            // return '$ ' + val + ' thousands';
            return val + " Activities / Proposals";
          },
        },
      },
    };
  if (
    typeof analyticsBarChartEl !== undefined &&
    analyticsBarChartEl !== null
  ) {
    // const analyticsBarChart = new ApexCharts(analyticsBarChartEl, analyticsBarChartConfig);
    // analyticsBarChart.render();
    const dataUrl = analyticsBarChartEl.getAttribute("data-url");
    fetch(dataUrl)
      .then((response) => response.json())
      .then((data) => {
        const thematicAreas = data.thematic_area;
        const seriesData = data.series;

        const maxTotal = Math.max(...seriesData.map((area) => area.total));

        const analyticsBarChartConfigModified = {
          ...analyticsBarChartConfig,
          series: [
            {
              name: "Total",
              data: seriesData.map((area) => area.total),
              color: "#1976D2",
            },
            {
              name: "Completed",
              data: seriesData.map((area) => area.completed),
              color: "#4caf50",
            },
            {
              name: "Pending",
              data: seriesData.map((area) => area.pending),
              color: "#ff9800",
            },
          ],
          xaxis: {
            categories: thematicAreas,
            ...analyticsBarChartConfig.xaxis,
          },
          yaxis: {
            min: 0,
            max: maxTotal, // Set the y-axis maximum to the calculated maximum total value
            tickAmount: 3,
            labels: {
              style: {
                colors: axisColor,
              },
              formatter: function (val) {
                return Math.floor(val);
              },
            },
            forceNiceScale: true,
          },
        };

        const analyticsBarChart = new ApexCharts(
          analyticsBarChartEl,
          analyticsBarChartConfigModified
        );
        analyticsBarChart.render();
      })
      .catch((error) => {
        console.log("Error fetching data:", error);
      });
  }

  // Analytics - Bar Chart
  // --------------------------------------------------------------------

  const categoryBarChartEl = document.querySelector("#categoryData"),
    categoryBarChartConfig = {
      chart: {
        height: 250,
        type: "bar",
        toolbar: {
          show: false,
        },
      },
      plotOptions: {
        bar: {
          horizontal: false,
          columnWidth: "20%",
          borderRadius: 3,
          startingShape: "rounded",
        },
      },
      dataLabels: {
        enabled: false,
      },
      colors: ["#1976D2", "#1976D2"],
      grid: {
        borderColor: borderColor,
        padding: {
          bottom: -8,
        },
      },
      xaxis: {
        axisBorder: {
          show: false,
        },
        axisTicks: {
          show: false,
        },
        labels: {
          style: {
            colors: axisColor,
          },
        },
      },
      yaxis: {
        min: 0,
        max: 30,
        tickAmount: 3,
        labels: {
          style: {
            colors: axisColor,
          },
        },
      },
      legend: {
        position: "top", // You can change the position to 'bottom', 'left', or 'right' as needed
        markers: {
          width: 12,
          height: 12,
          strokeWidth: 0,
          radius: 12,
          fillColors: ["#4caf50", "#ff9800", "#1976D2"], // Define legend marker colors
        },
      },
      tooltip: {
        y: {
          formatter: function (val) {
            // return '$ ' + val + ' thousands';
            return val + " Activities / Proposals";
          },
        },
      },
    };
  if (typeof categoryBarChartEl !== undefined && categoryBarChartEl !== null) {
    const dataUrl = categoryBarChartEl.getAttribute("data-url");
    fetch(dataUrl)
      .then((response) => response.json())
      .then((data) => {
        const category = data.category;
        const seriesData = data.series;

        const maxTotal = Math.max(...seriesData.map((area) => area.total));

        const categoryBarChartConfigModified = {
          ...categoryBarChartConfig,
          series: [
            {
              name: "Total",
              data: seriesData.map((area) => area.total),
              color: "#1976D2",
            },
            {
              name: "Completed",
              data: seriesData.map((area) => area.completed),
              color: "#4caf50",
            },
            {
              name: "Pending",
              data: seriesData.map((area) => area.pending),
              color: "#ff9800",
            },
          ],
          xaxis: {
            categories: category,
            ...categoryBarChartConfig.xaxis,
          },
          yaxis: {
            min: 0,
            max: maxTotal, // Set the y-axis maximum to the calculated maximum total value
            tickAmount: 3,
            labels: {
              style: {
                colors: axisColor,
              },
              formatter: function (val) {
                return Math.floor(val);
              },
            },
            forceNiceScale: true,
          },
        };

        const analyticsBarChart = new ApexCharts(
          categoryBarChartEl,
          categoryBarChartConfigModified
        );
        analyticsBarChart.render();
      })
      .catch((error) => {
        console.log("Error fetching data:", error);
      });
  }
  const complaintAnalyticEL = document.querySelector("#complaintThematicData"),
    complaintBarChartConfig = {
      chart: {
        height: 250,
        type: "bar",
        toolbar: {
          show: false,
        },
      },
      plotOptions: {
        bar: {
          horizontal: false,
          columnWidth: "20%",
          borderRadius: 3,
          startingShape: "rounded",
        },
      },
      dataLabels: {
        enabled: false,
      },
      colors: ["#1976D2", "#1976D2"],
      grid: {
        borderColor: borderColor,
        padding: {
          bottom: -8,
        },
      },
      xaxis: {
        axisBorder: {
          show: false,
        },
        axisTicks: {
          show: false,
        },
        labels: {
          style: {
            colors: axisColor,
          },
        },
      },
      yaxis: {
        min: 0,
        max: 30,
        tickAmount: 3,
        labels: {
          style: {
            colors: axisColor,
          },
        },
      },
      legend: {
        position: "top", // You can change the position to 'bottom', 'left', or 'right' as needed
        markers: {
          width: 12,
          height: 12,
          strokeWidth: 0,
          radius: 12,
          fillColors: ["#4caf50", "#ff9800", "#1976D2"], // Define legend marker colors
        },
      },
      tooltip: {
        y: {
          formatter: function (val) {
            // return '$ ' + val + ' thousands';
            return val + " Activities / Proposals";
          },
        },
      },
    };
  if (
    typeof complaintAnalyticEL !== undefined &&
    complaintAnalyticEL !== null
  ) {
    const dataUrl = complaintAnalyticEL.getAttribute("data-url");
    fetch(dataUrl)
      .then((response) => response.json())
      .then((data) => {
        const thematicAreas = data.thematic_area;
        const seriesData = data.series;

        const maxTotal = Math.max(...seriesData.map((area) => area.total));

        const analyticsBarChartConfigModified = {
          ...complaintBarChartConfig,
          series: [
            {
              name: "Total",
              data: seriesData.map((area) => area.total),
              color: "#1976D2",
            },
            {
              name: "Completed",
              data: seriesData.map((area) => area.completed),
              color: "#4caf50",
            },
            {
              name: "Pending",
              data: seriesData.map((area) => area.pending),
              color: "#ff9800",
            },
          ],
          xaxis: {
            categories: thematicAreas,
            ...complaintBarChartConfig.xaxis,
          },
          yaxis: {
            min: 0,
            max: maxTotal, // Set the y-axis maximum to the calculated maximum total value
            tickAmount: 3,
            labels: {
              style: {
                colors: axisColor,
              },
              formatter: function (val) {
                return Math.floor(val);
              },
            },
            forceNiceScale: true,
          },
        };

        const analyticsBarChart = new ApexCharts(
          complaintAnalyticEL,
          analyticsBarChartConfigModified
        );
        analyticsBarChart.render();
      })
      .catch((error) => {
        console.log("Error fetching data:", error);
      });
  }

  // Analytics - Bar Chart
  // --------------------------------------------------------------------

  const complaintCategoryBarChartEl = document.querySelector(
      "#complaintCategoryData"
    ),
    complaintCategoryBarChartConfig = {
      chart: {
        height: 250,
        type: "bar",
        toolbar: {
          show: false,
        },
      },
      plotOptions: {
        bar: {
          horizontal: false,
          columnWidth: "20%",
          borderRadius: 3,
          startingShape: "rounded",
        },
      },
      dataLabels: {
        enabled: false,
      },
      colors: ["#1976D2", "#1976D2"],
      grid: {
        borderColor: borderColor,
        padding: {
          bottom: -8,
        },
      },
      xaxis: {
        axisBorder: {
          show: false,
        },
        axisTicks: {
          show: false,
        },
        labels: {
          style: {
            colors: axisColor,
          },
        },
      },
      yaxis: {
        min: 0,
        max: 30,
        tickAmount: 3,
        labels: {
          style: {
            colors: axisColor,
          },
        },
      },
      legend: {
        position: "top", // You can change the position to 'bottom', 'left', or 'right' as needed
        markers: {
          width: 12,
          height: 12,
          strokeWidth: 0,
          radius: 12,
          fillColors: ["#4caf50", "#ff9800", "#1976D2"], // Define legend marker colors
        },
      },
      tooltip: {
        y: {
          formatter: function (val) {
            // return '$ ' + val + ' thousands';
            return val + " Activities / Proposals";
          },
        },
      },
    };
  if (
    typeof complaintCategoryBarChartEl !== undefined &&
    complaintCategoryBarChartEl !== null
  ) {
    const dataUrl = complaintCategoryBarChartEl.getAttribute("data-url");
    fetch(dataUrl)
      .then((response) => response.json())
      .then((data) => {
        const category = data.category;
        const seriesData = data.series;

        const maxTotal = Math.max(...seriesData.map((area) => area.total));

        const categoryBarChartConfigModified = {
          ...complaintCategoryBarChartConfig,
          series: [
            {
              name: "Total",
              data: seriesData.map((area) => area.total),
              color: "#1976D2",
            },
            {
              name: "Completed",
              data: seriesData.map((area) => area.completed),
              color: "#4caf50",
            },
            {
              name: "Pending",
              data: seriesData.map((area) => area.pending),
              color: "#ff9800",
            },
          ],
          xaxis: {
            categories: category,
            ...complaintCategoryBarChartConfig.xaxis,
          },
          yaxis: {
            min: 0,
            max: maxTotal, // Set the y-axis maximum to the calculated maximum total value
            tickAmount: 3,
            labels: {
              style: {
                colors: axisColor,
              },
              formatter: function (val) {
                return Math.floor(val);
              },
            },
            forceNiceScale: true,
          },
        };

        const analyticsBarChart = new ApexCharts(
          complaintCategoryBarChartEl,
          categoryBarChartConfigModified
        );
        analyticsBarChart.render();
      })
      .catch((error) => {
        console.log("Error fetching data:", error);
      });
  }

  // Referral - Line Chart
  // --------------------------------------------------------------------
  const referralLineChartEl = document.querySelector("#referralLineChart"),
    referralLineChartConfig = {
      series: [
        {
          data: [0, 150, 25, 100, 15, 149],
        },
      ],
      chart: {
        height: 100,
        parentHeightOffset: 0,
        parentWidthOffset: 0,
        type: "line",
        toolbar: {
          show: false,
        },
      },
      markers: {
        size: 6,
        colors: "transparent",
        strokeColors: "transparent",
        strokeWidth: 4,
        discrete: [
          {
            fillColor: cardColor,
            seriesIndex: 0,
            dataPointIndex: 5,
            strokeColor: config.colors.success,
            strokeWidth: 4,
            size: 6,
            radius: 2,
          },
        ],
        hover: {
          size: 7,
        },
      },
      dataLabels: {
        enabled: false,
      },
      stroke: {
        width: 4,
        curve: "smooth",
      },
      grid: {
        show: false,
        padding: {
          top: -25,
          bottom: -20,
        },
      },
      colors: [config.colors.success],
      xaxis: {
        show: false,
        axisBorder: {
          show: false,
        },
        axisTicks: {
          show: false,
        },
        labels: {
          show: false,
        },
      },
      yaxis: {
        labels: {
          show: false,
        },
      },
    };

  if (
    typeof referralLineChartEl !== undefined &&
    referralLineChartEl !== null
  ) {
    const referralLineChart = new ApexCharts(
      referralLineChartEl,
      referralLineChartConfig
    );
    referralLineChart.render();
  }

  // Conversion - Bar Chart
  // --------------------------------------------------------------------
  const conversionBarChartEl = document.querySelector("#conversionBarchart"),
    conversionBarChartConfig = {
      chart: {
        height: 100,
        stacked: true,
        type: "bar",
        toolbar: {
          show: false,
        },
        sparkline: {
          enabled: true,
        },
      },
      plotOptions: {
        bar: {
          columnWidth: "25%",
          borderRadius: 2,
          startingShape: "rounded",
        },
        distributed: true,
      },
      colors: [config.colors.primary, config.colors.warning],
      series: [
        {
          name: "New Clients",
          data: [
            75, 150, 225, 200, 35, 50, 150, 180, 50, 150, 240, 140, 75, 35, 60,
            120,
          ],
        },
        {
          name: "Retained Clients",
          data: [
            -100, -55, -40, -120, -70, -40, -60, -50, -70, -30, -60, -40, -50,
            -70, -40, -50,
          ],
        },
      ],
      grid: {
        show: false,
        padding: {
          top: 0,
          bottom: -10,
        },
      },
      legend: {
        show: false,
      },
      dataLabels: {
        enabled: false,
      },
      tooltip: {
        x: {
          show: false,
        },
      },
    };

  if (
    typeof conversionBarChartEl !== undefined &&
    conversionBarChartEl !== null
  ) {
    const conversionBarChart = new ApexCharts(
      conversionBarChartEl,
      conversionBarChartConfig
    );
    conversionBarChart.render();
  }

  // Impression - Donut Chart
  // --------------------------------------------------------------------
  const impressionDonutChartEl = document.querySelector(
      "#impressionDonutChart"
    ),
    impressionDonutChartConfig = {
      chart: {
        height: 185,
        fontFamily: "IBM Plex Sans",
        type: "donut",
      },
      dataLabels: {
        enabled: false,
      },
      grid: {
        padding: {
          bottom: -10,
        },
      },
      series: [80, 30, 60],
      labels: ["Social", "Email", "Search"],
      stroke: {
        width: 0,
        lineCap: "round",
      },
      colors: [
        config.colors.primary,
        config.colors.info,
        config.colors.warning,
      ],
      plotOptions: {
        pie: {
          donut: {
            size: "90%",
            labels: {
              show: true,
              name: {
                fontSize: "0.938rem",
                offsetY: 20,
              },
              value: {
                show: true,
                fontSize: "1.625rem",
                fontFamily: "Rubik",
                fontWeight: "500",
                color: headingColor,
                offsetY: -20,
                formatter: function (val) {
                  return val;
                },
              },
              total: {
                show: true,
                label: "Impression",
                color: config.colors.secondary,
                formatter: function (w) {
                  return w.globals.seriesTotals.reduce(function (a, b) {
                    return a + b;
                  }, 0);
                },
              },
            },
          },
        },
      },
      legend: {
        show: true,
        position: "bottom",
        horizontalAlign: "center",
        labels: {
          colors: axisColor,
          useSeriesColors: false,
        },
        markers: {
          width: 10,
          height: 10,
          offsetX: -3,
        },
      },
    };

  if (
    typeof impressionDonutChartEl !== undefined &&
    impressionDonutChartEl !== null
  ) {
    const impressionDonutChart = new ApexCharts(
      impressionDonutChartEl,
      impressionDonutChartConfig
    );
    impressionDonutChart.render();
  }

  // Conversion - Gradient Line Chart
  // --------------------------------------------------------------------
  const conversationChartEl = document.querySelector("#conversationChart"),
    conversationChartConfig = {
      series: [
        {
          data: [50, 100, 0, 60, 20, 30],
        },
      ],
      chart: {
        height: 40,
        type: "line",
        zoom: {
          enabled: false,
        },
        sparkline: {
          enabled: true,
        },
        toolbar: {
          show: false,
        },
      },
      dataLabels: {
        enabled: false,
      },
      tooltip: {
        enabled: false,
      },
      stroke: {
        curve: "smooth",
        width: 3,
      },
      grid: {
        show: false,
        padding: {
          top: 5,
          left: 10,
          right: 10,
          bottom: 5,
        },
      },
      colors: [config.colors.primary],
      fill: {
        type: "gradient",
        gradient: {
          shade: shadeColor,
          type: "horizontal",
          gradientToColors: undefined,
          opacityFrom: 0,
          opacityTo: 0.9,
          stops: [0, 30, 70, 100],
        },
      },
      xaxis: {
        labels: {
          show: false,
        },
        axisBorder: {
          show: false,
        },
        axisTicks: {
          show: false,
        },
      },
      yaxis: {
        labels: {
          show: false,
        },
      },
    };
  if (
    typeof conversationChartEl !== undefined &&
    conversationChartEl !== null
  ) {
    const conversationChart = new ApexCharts(
      conversationChartEl,
      conversationChartConfig
    );
    conversationChart.render();
  }

  // Income - Gradient Line Chart
  // --------------------------------------------------------------------
  const incomeChartEl = document.querySelector("#incomeChart"),
    incomeChartConfig = {
      series: [
        {
          data: [40, 70, 38, 90, 40, 65],
        },
      ],
      chart: {
        height: 40,
        type: "line",
        zoom: {
          enabled: false,
        },
        sparkline: {
          enabled: true,
        },
        toolbar: {
          show: false,
        },
      },
      dataLabels: {
        enabled: false,
      },
      tooltip: {
        enabled: false,
      },
      stroke: {
        curve: "smooth",
        width: 3,
      },
      grid: {
        show: false,
        padding: {
          top: 10,
          left: 10,
          right: 10,
          bottom: 0,
        },
      },
      colors: [config.colors.warning],
      fill: {
        type: "gradient",
        gradient: {
          shade: shadeColor,
          type: "horizontal",
          gradientToColors: undefined,
          opacityFrom: 0,
          opacityTo: 0.9,
          stops: [0, 30, 70, 100],
        },
      },
      xaxis: {
        labels: {
          show: false,
        },
        axisBorder: {
          show: false,
        },
        axisTicks: {
          show: false,
        },
      },
      yaxis: {
        labels: {
          show: false,
        },
      },
    };
  if (typeof incomeChartEl !== undefined && incomeChartEl !== null) {
    const incomeChart = new ApexCharts(incomeChartEl, incomeChartConfig);
    incomeChart.render();
  }

  // Registrations Bar Chart
  // --------------------------------------------------------------------
  const registrationsBarChartEl = document.querySelector(
      "#registrationsBarChart"
    ),
    registrationsBarChartConfig = {
      chart: {
        height: 95,
        width: 155,
        type: "bar",
        toolbar: {
          show: false,
        },
      },
      plotOptions: {
        bar: {
          barHeight: "80%",
          columnWidth: "50%",
          startingShape: "rounded",
          endingShape: "rounded",
          borderRadius: 2,
          distributed: true,
        },
      },
      grid: {
        show: false,
        padding: {
          top: -20,
          bottom: -20,
          left: 0,
          right: 0,
        },
      },
      colors: [
        config.colors_label.warning,
        config.colors_label.warning,
        config.colors_label.warning,
        config.colors_label.warning,
        config.colors.warning,
        config.colors_label.warning,
        config.colors_label.warning,
      ],
      dataLabels: {
        enabled: false,
      },
      series: [
        {
          data: [30, 55, 45, 95, 70, 50, 65],
        },
      ],
      legend: {
        show: false,
      },
      xaxis: {
        categories: ["M", "T", "W", "T", "F", "S", "S"],
        axisBorder: {
          show: false,
        },
        axisTicks: {
          show: false,
        },
        labels: {
          show: false,
        },
      },
      yaxis: {
        labels: {
          show: false,
        },
      },
    };
  if (
    typeof registrationsBarChartEl !== undefined &&
    registrationsBarChartEl !== null
  ) {
    const registrationsBarChart = new ApexCharts(
      registrationsBarChartEl,
      registrationsBarChartConfig
    );
    registrationsBarChart.render();
  }

  // Sales Bar Chart
  // --------------------------------------------------------------------
  const salesBarChartEl = document.querySelector("#salesChart"),
    salesBarChartConfig = {
      chart: {
        height: 120,
        parentHeightOffset: 0,
        type: "bar",
        toolbar: {
          show: false,
        },
      },
      plotOptions: {
        bar: {
          barHeight: "100%",
          columnWidth: "25px",
          startingShape: "rounded",
          endingShape: "rounded",
          borderRadius: 5,
          distributed: true,
          colors: {
            backgroundBarColors: [
              config.colors_label.primary,
              config.colors_label.primary,
              config.colors_label.primary,
              config.colors_label.primary,
            ],
            backgroundBarRadius: 5,
          },
        },
      },
      grid: {
        show: false,
        padding: {
          top: -30,
          left: -12,
          bottom: 10,
        },
      },
      colors: [config.colors.primary],
      dataLabels: {
        enabled: false,
      },
      series: [
        {
          data: [60, 35, 25, 75, 15, 42, 85],
        },
      ],
      legend: {
        show: false,
      },
      xaxis: {
        categories: ["S", "M", "T", "W", "T", "F", "S"],
        axisBorder: {
          show: false,
        },
        axisTicks: {
          show: false,
        },
        labels: {
          style: {
            colors: axisColor,
            fontSize: "13px",
          },
        },
      },
      yaxis: {
        labels: {
          show: false,
        },
      },
      responsive: [
        {
          breakpoint: 1440,
          options: {
            plotOptions: {
              bar: {
                columnWidth: "30%",
              },
            },
          },
        },
        {
          breakpoint: 1200,
          options: {
            plotOptions: {
              bar: {
                columnWidth: "15%",
              },
            },
          },
        },
        {
          breakpoint: 768,
          options: {
            plotOptions: {
              bar: {
                columnWidth: "12%",
              },
            },
          },
        },
        {
          breakpoint: 450,
          options: {
            plotOptions: {
              bar: {
                columnWidth: "19%",
              },
            },
          },
        },
      ],
    };
  if (typeof salesBarChartEl !== undefined && salesBarChartEl !== null) {
    const salesBarChart = new ApexCharts(salesBarChartEl, salesBarChartConfig);
    salesBarChart.render();
  }

  // Growth - Radial Bar Chart
  // --------------------------------------------------------------------
  const growthRadialChartEl = document.querySelector("#growthRadialChart"),
    growthRadialChartConfig = {
      chart: {
        height: 230,
        fontFamily: "IBM Plex Sans",
        type: "radialBar",
        sparkline: {
          show: true,
        },
      },
      grid: {
        show: false,
        padding: {
          top: -25,
        },
      },
      plotOptions: {
        radialBar: {
          size: 100,
          startAngle: -135,
          endAngle: 135,
          offsetY: 10,
          hollow: {
            size: "55%",
          },
          track: {
            strokeWidth: "50%",
            background: cardColor,
          },
          dataLabels: {
            value: {
              offsetY: -15,
              color: headingColor,
              fontFamily: "Rubik",
              fontWeight: 500,
              fontSize: "26px",
            },
            name: {
              fontSize: "15px",
              color: config.colors.secondary,
              offsetY: 24,
            },
          },
        },
      },
      colors: [config.colors.danger],
      fill: {
        type: "gradient",
        gradient: {
          shade: "dark",
          type: "horizontal",
          shadeIntensity: 0.5,
          gradientToColors: [config.colors.primary],
          inverseColors: true,
          opacityFrom: 1,
          opacityTo: 1,
          stops: [0, 100],
        },
      },
      stroke: {
        dashArray: 3,
      },
      series: [78],
      labels: ["Growth"],
    };

  if (
    typeof growthRadialChartEl !== undefined &&
    growthRadialChartEl !== null
  ) {
    const growthRadialChart = new ApexCharts(
      growthRadialChartEl,
      growthRadialChartConfig
    );
    growthRadialChart.render();
  }
})();
