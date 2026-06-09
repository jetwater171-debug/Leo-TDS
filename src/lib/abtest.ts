// Simple A/B Testing module in TypeScript

export function getRandomItem<T>(items: T[]): T {
  const randomIndex = Math.floor(Math.random() * items.length);
  return items[randomIndex];
}

export function getWeightedItem<T>(items: T[], weights: number[]): T {
  const total = weights.reduce((a, b) => a + b, 0);
  if (total <= 0) {
    return getRandomItem(items);
  }
  const rand = Math.floor(Math.random() * total) + 1;
  let cumulative = 0;
  for (let i = 0; i < items.length; i++) {
    cumulative += weights[i] || 0;
    if (rand <= cumulative) {
      return items[i];
    }
  }
  return items[items.length - 1];
}

export function selectDistributed<T>(
  items: T[],
  distribution: string,
  weights: number[]
): { item: T; index: number } {
  if (items.length === 0) {
    throw new Error('Lista de itens de distribuição vazia.');
  }

  const item = (distribution === 'weighted' && weights && weights.length > 0)
    ? getWeightedItem(items, weights)
    : getRandomItem(items);

  return {
    item,
    index: items.indexOf(item)
  };
}

// Helper to compute wins using Thompson Sampling (standard Beta distribution simulation)
// Standard Marsaglia-Tsang method for generating Gamma samples
export function randomBeta(alpha: number, beta: number): number {
  const a = gammaSample(alpha);
  const b = gammaSample(beta);
  return a / (a + b);
}

function gammaSample(shape: number): number {
  if (shape < 1.0) {
    const shapePlus = shape + 1.0;
    const u = Math.random();
    return gammaSample(shapePlus) * Math.pow(u, 1.0 / (shapePlus - 1));
  }

  const d = shape - 1.0 / 3.0;
  const c = 1.0 / Math.sqrt(9.0 * d);

  while (true) {
    let x = gaussian();
    let v = 1.0 + c * x;
    while (v <= 0) {
      x = gaussian();
      v = 1.0 + c * x;
    }

    v = v * v * v;
    const u = Math.random();

    if (u < 1.0 - 0.0331 * x * x * x * x) {
      return d * v;
    }

    if (Math.log(u) < 0.5 * x * x + d * (1 - v + Math.log(v))) {
      return d * v;
    }
  }
}

function gaussian(): number {
  let u = 0, v = 0;
  while (u === 0) u = Math.random();
  while (v === 0) v = Math.random();
  return Math.sqrt(-2.0 * Math.log(u)) * Math.cos(2.0 * Math.PI * v);
}
