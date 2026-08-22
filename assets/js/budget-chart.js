const { useState, useEffect } = React;
const { PieChart, Pie, Cell, Tooltip, ResponsiveContainer, Legend } = Recharts;

const COLORS = {
  transport: '#3b82f6', // blue-500
  stay: '#8b5cf6', // violet-500
  activities: '#ec4899', // pink-500
  meals: '#f59e0b', // amber-500
  misc: '#10b981', // emerald-500
};

const LABELS = {
  transport: 'Transport',
  stay: 'Accommodation',
  activities: 'Activities',
  meals: 'Meals',
  misc: 'Miscellaneous'
};

const BudgetDonutChart = () => {
  const [data, setData] = useState([]);
  const [activeIndex, setActiveIndex] = useState(-1);

  const updateData = () => {
    if (window.BUDGET_DATA && window.BUDGET_DATA.budget) {
      const b = window.BUDGET_DATA.budget;
      const chartData = [
        { name: 'transport', value: b.transport },
        { name: 'stay', value: b.stay },
        { name: 'activities', value: b.activities },
        { name: 'meals', value: b.meals },
        { name: 'misc', value: b.misc },
      ].filter(item => item.value > 0);
      setData(chartData);
    }
  };

  useEffect(() => {
    updateData();
    window.addEventListener('budgetDataLoaded', updateData);
    return () => window.removeEventListener('budgetDataLoaded', updateData);
  }, []);

  const total = data.reduce((sum, item) => sum + item.value, 0);

  if (total === 0) {
    return (
      <div className="flex items-center justify-center h-64 bg-slate-50 rounded-xl border border-dashed border-slate-300">
        <p className="text-slate-500 font-medium">No budget data available.</p>
      </div>
    );
  }

  const CustomTooltip = ({ active, payload }) => {
    if (active && payload && payload.length) {
      const data = payload[0].payload;
      const percent = ((data.value / total) * 100).toFixed(1);
      return (
        <div className="bg-white/80 backdrop-blur-md border border-white/40 shadow-lg rounded-xl p-3">
          <p className="font-semibold text-slate-800 flex items-center gap-2">
            <span className="w-3 h-3 rounded-full inline-block" style={{ backgroundColor: COLORS[data.name] }}></span>
            {LABELS[data.name]}
          </p>
          <p className="text-slate-600 mt-1">
            Amount: <span className="font-medium">${data.value.toFixed(2)}</span>
          </p>
          <p className="text-slate-600">
            Share: <span className="font-medium">{percent}%</span>
          </p>
        </div>
      );
    }
    return null;
  };

  const renderLegend = (props) => {
    const { payload } = props;
    return (
      <ul className="flex flex-wrap justify-center gap-4 mt-4">
        {payload.map((entry, index) => (
          <li key={`item-${index}`} className="flex items-center gap-2 text-sm text-slate-600 font-medium">
            <span className="w-3 h-3 rounded-full" style={{ backgroundColor: entry.color }}></span>
            {LABELS[entry.value]}
          </li>
        ))}
      </ul>
    );
  };

  return (
    <div className="h-72 w-full">
      <ResponsiveContainer width="100%" height="100%">
        <PieChart>
          <Pie
            data={data}
            cx="50%"
            cy="45%"
            innerRadius={60}
            outerRadius={90}
            paddingAngle={2}
            dataKey="value"
            onMouseEnter={(_, index) => setActiveIndex(index)}
            onMouseLeave={() => setActiveIndex(-1)}
          >
            {data.map((entry, index) => (
              <Cell 
                key={`cell-${index}`} 
                fill={COLORS[entry.name]} 
                stroke={COLORS[entry.name]}
                strokeWidth={activeIndex === index ? 4 : 0}
                style={{
                  filter: activeIndex === index ? `drop-shadow(0px 0px 8px ${COLORS[entry.name]}80)` : 'none',
                  transition: 'all 0.3s ease',
                  cursor: 'pointer',
                  opacity: activeIndex === index || activeIndex === -1 ? 1 : 0.5
                }}
              />
            ))}
          </Pie>
          <Tooltip content={<CustomTooltip />} />
          <Legend content={renderLegend} />
        </PieChart>
      </ResponsiveContainer>
    </div>
  );
};

const root = ReactDOM.createRoot(document.getElementById('budget-donut-root'));
root.render(<BudgetDonutChart />);
